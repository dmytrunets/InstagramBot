<?php
/**
 * App
 *
 * @copyright Copyright © 2019 InComm. All rights reserved.
 * @author    ydmytrunets@incomm.com
 */

class Cron
{
    /**
     * @var InstagramBot
     */
    private $instagramBot;

    public function __construct($credo, $instagramBot)
    {
        $this->instagramBot = $instagramBot;
    }

    public function index()
    {
        $i = 1;
        while(1) {
            try{
                echo "\nCount: " . $i++;
                $this->instagramBot->scenarioOne();
                sleep(8 * 60);
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }
    }
}
