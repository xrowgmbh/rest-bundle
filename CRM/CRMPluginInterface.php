<?php

namespace xrow\restBundle\CRM;

interface CRMPluginInterface
{
    public function loadUser($username, $password, $userRepository);

    public function getUserData($user);

    public function getAccountData($user);

    public function getUserSubscriptions($user);

    public function getUserSubscription($user, $subscriptionId);
}