<?php

class BaseUser
{
    /**
     * @return string
     */
    public function getFullName()
    {
    }
}

class User extends BaseUser
{
    const DEFAULT_PATH = 'some/default/path';
    const ROLE_DISALLOWED = 1;
    const ROLE_ALLOWED = 2;
    const ROLE_FORBIDDEN = 3;

    public $additionalData;

    public function __construct()
    {
    }

    /**
     * Check is user is equal to another user
     */
    public function isEqualTo(BaseUser $user)
    {
    }

    /**
     * Get data from this demo class
     *
     * @param string $username
     *
     * @return array
     */
    public function setUsername($username)
    {
    }

    /**
     * Set additional data
     *
     * @array $data
     */
    public function setAdditionalData(array $data)
    {
        $this->additionalData = $data;
    }

    /**
     * @return DateTime date object
     */
    public function getCreatedDate()
    {
    }

    /**
     * @param DateTime $date
     */
    public function setCreatedDate($date)
    {
        $this->json = '[1,2,3,4,5,"asd"]';
    }

    /**
     * Dummy method that triggers trace
     */
    public function ensure()
    {
        ss(debug_backtrace());
        Sage::trace();
    }
}

class UserManager
{
    private $user;

    /**
     * Get user from manager
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Debug specific user
     *
     * @param User $user
     */
    public function debugUser($user)
    {
        $this->user = $user;
        s($this->getUser());
    }

    /**
     * Ensure user (triggers ensure() method on \User object that trace)
     *
     * @void
     */
    public function ensureUser($userManager)
    {
        $this->user->ensure();
    }
}

$user = new User();
$user->setAdditionalData(array(
        'last_login'             => date('Y-m-d'),
        'current_unix_timestamp' => time(),
        'random_rgb_color_code'  => '#FF9900',
        'impressions'            => 60,
        'nickname'               => 'Someuser',
    )
);
$user->setCreatedDate(date('Y-m-d'));
$userManager = new UserManager();

for ($i = 1; $i < 6; $i++) {
    $tabularData[] = array(
        'date'        => "2013-01-0{$i}",
        'allowed'     => $i % 3 == 0,
        'action'      => "action {$i}",
        'clicks'      => rand(100, 50000),
        'impressions' => rand(10000, 500000),
    );

    //    if ($i % 2 == 0) {
    //        unset($tabularData[$i - 1]['clicks']);
    //    }
}

$nestedArray = array();

for ($i = 1; $i < 6; $i++) {
    $nestedArray["user group {$i}"] = array(
        "user {$i}" => array(
            'name'    => "Name {$i}",
            'surname' => "Surname {$i}"
        ),

        'data' => array(
            'conversions' => rand(100, 5000),
            'spent'       => array('currency' => 'EUR', 'amount' => rand(10000, 500000))
        ),
    );
}

$userManager->debugUser($user);
s($userManager, $tabularData);
s($nestedArray);

ss($user);
~ss('PHP VERSION ' . PHP_VERSION);
$userManager->ensureUser($userManager);
