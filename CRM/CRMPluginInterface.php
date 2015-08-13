<?php

namespace xrow\restBundle\CRM;

interface CRMPluginInterface
{
    public function loadUser($username, $password, $userRepository);

    public function getUser($user, $refreshSession = false);

    public function getAccount($user, $refreshSession = false);

    public function getSubscriptions($user, $refreshSession = false);

    public function getSubscription($user, $subscriptionId, $refreshSession = false);

    public function checkPassword($loginData);

    public function updateUser($user, $newData);
}