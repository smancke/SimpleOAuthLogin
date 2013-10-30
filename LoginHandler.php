<?php

/**
 * Bundles some methods for implementing the login flow
 *
 * A typical code for using this class would be:
 * if (isset($_GET['code']) 
 *    && $loginHandler->login()
 *    && $loginHandler->ensureUserAndStartSession($userMgr)
 *    && $loginHandler->updateMyCircles($userMgr)
 *    ) {
 *      Header('Location: '.$loginHandler->getNextAction('/rating'));
 *      die();
 * } else {
 *      $errorMessage = $loginHandler->getError();
 * }
 */
class LoginHandler {

    protected $loginProvider;
    protected $error;

    function __construct($loginProvider) {
        $this->loginProvider = $loginProvider;
    }

    /**
     * Returns previous errors
     */
    function getError() {
        return $this->error;
    }

    /**
     * Does the redirect to the authorisation server for login
     */
    function redirectToAuthorisationServer() {
        global $_GET;
        $loginInfo = ['state' => md5(rand().rand().rand())];
        if (isset($_GET['nextAction'])) {
            $loginInfo['nextAction'] = $_GET['nextAction'];
        }

        setcookie('l', json_encode($loginInfo), 0, '/');

        $authUrl = $this->loginProvider->getAuthUrl($loginInfo['state'], true);
        Header('Location: '. $authUrl);
    }

    /**
     * As part of the login it verifies the state parameter against the cookie
     * returns true on success, false on errors
     */
    protected function verifyState() {
        global $_GET, $_COOKIE;

        $loginInfo = isset($_COOKIE['l']) ? json_decode($_COOKIE['l']) : [];
        
        if (isset($_GET['state'])
            && $loginInfo
            && $loginInfo->state == $_GET['state']) {
            return true;
        }
        $this->error = '<h1>Error on google sign in.</h1>(Could not verify state parameter)';
        return false;
    }

    /**
     * Verifies the state paramter and exchanges the AuthCode against the access_token
     * returns true on success, false on errors
     */
    function login() {
        global $_GET;
        
        if (!$this->verifyState())
            return false;

        if (!$this->loginProvider->exchangeAuthCode($_GET['code'])) {
            $this->error = '<h1>Error on google sign in.</h1>(Could not get access_token)';
            return false;
        }
        return true;
    }

    /**
     * Creates or updates the user within the database
     * and starts a session for the user.
     * returns true on success, false on errors
     */
    function ensureUserAndStartSession($userMgr) {
        $userTokenInfo = $this->loginProvider->getTokenInfo();
        $me = $this->loginProvider->getMe();
            
        if (!property_exists($userTokenInfo, 'email')) {
            $this->error = '<h1>Error on google sign in.</h1>(Could not get user info)';
            return false;
        }

        $userMgr->setAndCreateUserIfNotExists('google', $userTokenInfo->user_id, $userTokenInfo->email, $me->displayName, $me->image->url);
        
        //create session         
        $sessionId = $userMgr->startSession();
        setcookie('s', $sessionId, 0, '/');            
        return true;
    }

    /**
     * Stores the People in the circles to the database.
     * returns true on success, false on errors
     */
    function updateMyCircles($userMgr) {
        $mePeople = null;
        do {
            $mePeople = $this->loginProvider->getMePeople( $mePeople != null && property_exists($mePeople, 'nextPageToken') ? $mePeople->nextPageToken : null );
            if ($mePeople && property_exists($mePeople, 'items')) {
                $userMgr->updateMyContacts($mePeople->items);
            } else {
                $this->error = '<h1>Error on google sign in.</h1>(Could not get contacts people)';
                return false;
            }
        } while (property_exists($mePeople, 'nextPageToken'));
        return true;
    }

    /**
     * Retuns the next action for forwarding
     */
    function getNextAction($default='') {
        $loginInfo = isset($_COOKIE['l']) ? json_decode($_COOKIE['l']) : [];
        if ($loginInfo && isset($loginInfo->nextAction))
            return $loginInfo->nextAction;
        return $default;
    }

    /**
     * invalidates the session, if any
     */
    function logout($userMgr) {
        if (isset($_COOKIE['s'])) {
            $userMgr->invalidateSession($_COOKIE['s']);
        }

    }
    
}

?>