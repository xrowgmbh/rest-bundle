<?php

namespace xrow\restBundle\CRM;

interface CRMPluginInterface
{
    public function loadUser($username, $password, $userRepository);

    public function getUser($user);

    public function getAccount($user);

    public function getSubscriptions($user);

    public function checkPassword($loginData);

    public function updateUser($user, $newData);

    public function getSubscription($user, $subscriptionId);
}