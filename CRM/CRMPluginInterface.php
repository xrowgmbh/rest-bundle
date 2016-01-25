<?php

namespace xrow\restBundle\CRM;

interface CRMPluginInterface
{
    public function loadUser($username, $password);

    public function getUser($user);

    public function getAccount($user);

    public function getSubscriptions($user);

    public function getSubscription($user, $subscriptionId);

    public function checkPassword($loginData);

    public function updateUser($user, $newData);
}