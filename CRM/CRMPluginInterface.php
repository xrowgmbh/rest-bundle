<?php

namespace xrow\restBundle\CRM;

interface CRMPluginInterface
{
    public function loadUser($username, $password, $userRepository);

    public function getUserData($userId);

    public function getUserSubscriptions($userId);

    public function getUserSubscription($userId, $subscriptionId);
}