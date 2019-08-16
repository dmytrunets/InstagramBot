<?php
/**
 * Instagram bot
 */

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

/**
 * Class Instagram scenario
 */
class InstagramBot
{
    /** @var int limit for period 6m 30 sec */
    public const LIMIT_ACTION_UNFOLLOW = 20;

    /** @var int limit for period 6m 30 sec */
    private const LIMIT_ACTION_LIKE = 56;

    private const URL_people_suggested = 'https://www.instagram.com/explore/people/suggested/';
    private const URL_PROFILE = 'https://www.instagram.com/{username}/';
    private const URL_FOLLOWERS = 'https://www.instagram.com/{username}/followers/';
    private const URL_FOLLOWING = 'https://www.instagram.com/{username}/following/';

    public const DEFAULT_COUNT_FOLLOWERS = 70;

    public const SOURCE_FOLLOWERS   = 1;
    public const SOURCE_FOLLOWING   = 2;
    public const MAX_LOAD_FROM_LIST = 100;

    /** @var RemoteWebDriver  */
    private $driver;

    /**
     * @var array Format ['login' => '', 'password' => '']
     */
    private $cred;

    /**
     * Instagram constructor.
     *
     * @param RemoteWebDriver $driver
     * @param array           $cred
     */
    public function __construct(RemoteWebDriver $driver, array $cred)
    {
        $this->driver = $driver;
        $this->cred = $cred;
    }

    /**
     * @param string $username
     * @param int    $source
     *
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function scenarioOne(string $username, int $source)
    {
        $users = [];
        $this->openUserProfile($username);
        $countProfiles = $this->getCountFollowers($source);
        $this->log("Profile: {$username}");
        $sourceTitle = $this->getSourceTitle($source);
        $this->log("Count {$this->getSourceTitle($source)}: {$countProfiles}");

        if ($countProfiles > static::MAX_LOAD_FROM_LIST) {
            $countProfiles = static::MAX_LOAD_FROM_LIST;
        }

        switch ($source){
            case 1:
                $this->openPopupFollowers();
                break;
            case 2:
                $this->openPopupFollowing();
                break;
        }

        sleep(2);
        $this->loadFullProfileListInPopUp($countProfiles);
        $this->scrollToFirstItemInPopUp();

        $countException = 0;
        $countUnfollow = 0;
        for ($i = 1; $i <= $countProfiles; $i++) {
            if ($countUnfollow === self::LIMIT_ACTION_UNFOLLOW) {
                break;
            }
            try {
                // get user name
                $elm = $this->driver->findElement(WebDriverBy::xpath(("/html/body/div[3]/div/div[2]/ul/div/li[$i]/div/div[1]/div[2]/div[1]/a")));
                $this->log("Username $i: " . $elm->getText());

                $users[] = [$elm->getText()];
//                $this->followFromPopUp($i);
                if ($this->unFollowFromPopUp($i)) {
                    $countUnfollow++;
                }

            } catch (\Exception $e) {
                if (++$countException > 2) {
                    throw $e;
                }
                echo "\nError: " . $e->getMessage();
            }
        }

        $dir = __DIR__ . "/db/{$username}";
        if (!is_dir($dir)) {
            @mkdir($dir);
        }

        $file = __DIR__ . "/db/{$username}/$sourceTitle.txt";
        if (file_exists($file)) {
            @unlink($file);
        }

        file_put_contents(__DIR__ . "/db/{$username}/$sourceTitle.txt", json_encode($users));
    }

    /**
     * Get Source title
     *
     * @param $source
     *
     * @return string
     */
    public function getSourceTitle($source): string
    {
        if ($source === static::SOURCE_FOLLOWERS) $i = 2;
        if ($source === static::SOURCE_FOLLOWING) $i = 3;

        $elm = $this->driver->findElement(WebDriverBy::xpath(sprintf('//*[@id="react-root"]/section/main/div/header/section/ul/li[%d]/a', $i)));

        return explode(' ', $elm->getText())[1];
    }

    /**
     * Click follow btn from popup Followers/Following
     *
     * @param int $i Position in popup list
     *
     * @return bool
     */
    public function followFromPopUp(int $i): bool
    {
        $elmActionBtn = $this->driver->findElement(WebDriverBy::xpath(("/html/body/div[3]/div/div[2]/ul/div/li[$i]/div/div[2]/button")));

        if ($elmActionBtn->getText() === 'Follow') {
            $elmActionBtn->sendKeys('1')->click();
            return true;
        }
        return false;
    }

    /**
     * Click un follow btn from popup Followers/Following
     *
     * @param int $i Position in popup list
     *
     * @return bool
     */
    public function unFollowFromPopUp(int $i): bool
    {
        $elmActionBtn = $this->driver->findElement(WebDriverBy::xpath(("/html/body/div[3]/div/div[2]/ul/div/li[$i]/div/div[2]/button")));

        if ($elmActionBtn->getText() === 'Following') {
            // click unfollow btn
            $elmActionBtn->sendKeys('1')->click();
            sleep(1);

            // click configm unfollow action in dialog
            $this->driver->findElement(WebDriverBy::xpath(("/html/body/div/div/div/div/button[1]")))->sendKeys('1')->click();
            sleep(1);
            return true;
        }

        return false;
    }

    /**
     * Open popup follower on profile page
     */
    public function openPopupFollowers(): bool
    {
        try {
            // click on follower btn
            $this->driver->findElement(WebDriverBy::xpath(('//*[@id="react-root"]/section/main/div/header/section/ul/li[2]/a')))->sendKeys('1')->click();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Open popup following on profile page
     */
    public function openPopupFollowing(): bool
    {
        try {
            // click on following btn
            $this->driver->findElement(WebDriverBy::xpath(('//*[@id="react-root"]/section/main/div/header/section/ul/li[3]/a')))->sendKeys('1')->click();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get count followers on profile
     *
     * @param $source
     *
     * @return int
     */
    public function getCountFollowers($source): int
    {
        try {
            if ($source === static::SOURCE_FOLLOWERS) $i = 2;
            if ($source === static::SOURCE_FOLLOWING) $i = 3;

            $elm = $this->driver->findElement(WebDriverBy::xpath(sprintf('//*[@id="react-root"]/section/main/div/header/section/ul/li[%d]/a/span', $i)));
            $countFollower = (int) str_replace(',', '', $elm->getText());
        } catch (\Exception $e) {
            $countFollower = static::DEFAULT_COUNT_FOLLOWERS;
            return false;
        }

        return $countFollower;
    }

    /**
     * Open user profile
     *
     * @param $username
     *
     * @return bool
     */
    public function openUserProfile($username): bool
    {
        $this->log("Open profile {$username}");
        $this->driver->navigate()->to(str_replace('{username}', $username, static::URL_PROFILE));

        return true;
    }

    /**
     * Login
     */
    public function loginPage(): bool
    {
        $this->driver->navigate()->to('https://www.instagram.com/accounts/login/?source=auth_switcher');
        $this->driver->findElement(WebDriverBy::xpath("//INPUT[@name='username']"))->sendKeys($this->cred['username'])->click();
        $this->driver->findElement(WebDriverBy::xpath('//INPUT[@name="password"]'))->sendKeys($this->cred['password'])->click();
        $this->driver->findElement(WebDriverBy::xpath('//BUTTON[@type=\'submit\']'))->sendKeys('1')->click();
        sleep(3);

        $this->clickNotInstallAppAndContinue();
        $this->clickDeclineNotification();

        return true;
    }

    /**
     * Decline popup install instagram app and use web instead
     */
    protected function clickNotInstallAppAndContinue()
    {
        try {
            $this->driver->findElement(WebDriverBy::xpath('//*[@id="react-root"]/div/div[2]/a[2]'))->sendKeys('1')->click();
        } catch (\Exception $e) {}

        try {
            $this->driver->findElement(WebDriverBy::xpath('/html/body/div[2]/div/div/div[3]/button[2]'))->sendKeys('1')->click();
        } catch (\Exception $e) {}
    }

    /**
     * Decline browser popup PWA notification
     */
    protected function clickDeclineNotification()
    {
        try {
            $this->driver->findElement(WebDriverBy::xpath('/html/body/div[3]/div/div/div[3]/button[2]'))->sendKeys('1')->click();
        } catch (\Exception $e) {}
    }

    /**
     * @param $countFollower
     *
     * @return array
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function loadFullProfileListInPopUp($countFollower): bool
    {
        $scrollValue = 0;
        $countException = 0;

        if ($countFollower > static::MAX_LOAD_FROM_LIST) {
            $countFollower = static::MAX_LOAD_FROM_LIST;
        }

        // load full list
        for ($i = 1; $i <= $countFollower * 3; $i++) {
            try {
                if ($i % 20 === 0) {
                    $scrollValue += 500;
                    // scroll
                    $elmDialog = $this->driver->findElement(WebDriverBy::xpath(('/html/body/div[3]/div/div[2]')));
                    $this->driver->executeScript("arguments[0].scroll(0, $scrollValue)", [$elmDialog]);
                    sleep(1);
                    $percentage = $i * 100 / $countFollower / 3;
                    $this->log("Loaded: {$percentage}%");
                }
            } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
                echo "\nError: ";
                $elmDialog = $this->driver->findElement(WebDriverBy::xpath(('/html/body/div[3]/div/div[2]')));
                $this->driver->executeScript('arguments[0].scroll(0, 500)', [$elmDialog]);
                sleep(1);
                if (++$countException > 2) {
                    throw $e;
                }
            }
        }

        return true;
    }

    /**
     * Scroll to first item in popup
     */
    public function scrollToFirstItemInPopUp(): bool
    {
        $elmDialog = $this->driver->findElement(WebDriverBy::xpath(('/html/body/div[3]/div/div[2]')));
        $this->driver->executeScript('arguments[0].scroll(0, 0)', [$elmDialog]);

        return true;
    }

    /**
     * Like n random photos
     *
     * @param $n
     *
     * @return bool
     */
    public function likeRandomPhotos($n): bool
    {
        $this->log("Make {$n} likes");
        if ($this->isPrivateProfile()) {
            return true;
        }

        $randomPositionPhoto = array_rand(range(1, 9), $n);
        $randomPositionPhoto = is_int($randomPositionPhoto) ? [$randomPositionPhoto] : $randomPositionPhoto;

        foreach ($randomPositionPhoto as $position) {
            if (!$this->likePhoto($position)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Follow from page
     *
     * @return bool
     */
    public function followFromPage(): bool
    {
        if ($this->isPrivateProfile()) {
            return true;
        }

        $elmActionBtn = $this->driver->findElement(WebDriverBy::xpath('//*[@id="react-root"]/section/main/div/header/section/div[1]/span/span[1]/button'));
        if ($elmActionBtn->getText() === 'Follow') {
            $elmActionBtn->sendKeys('1')->click();
            return true;
        }
        return false;
    }

    /**
     * Un Follow from page
     *
     * @return bool
     */
    public function unFollowFromPage(): bool
    {
        if ($this->isPrivateProfile()) {
            return true;
        }

        $elmActionBtn = $this->driver->findElement(WebDriverBy::xpath('//*[@id="react-root"]/section/main/div/header/section/div[1]/span/span[1]/button'));
        if ($elmActionBtn->getText() === 'Following') {
            // click unfollow btn
            $elmActionBtn->sendKeys('1')->click();
            sleep(1);

            // click configm unfollow action in dialog
            $this->driver->findElement(WebDriverBy::xpath(("/html/body/div[3]/div/div/div[3]/button[1]")))->sendKeys('1')->click();
            sleep(1);

            return true;
        }

        return false;
    }

    /**
     * Like photos
     *
     * @param $position
     *
     * @return bool
     */
    public function likePhoto($position): bool
    {
        sleep(1);
        $this->openPhotoPreview($position);
        $instaX = 3;

        try {
            sleep(2);
            $elmPhotoPreviewSpan = $this->driver->findElement(WebDriverBy::xpath("/html/body/div[{$instaX}]/div[2]/div/article/div[2]/section[1]/span[1]/button/span"));
            if ($elmPhotoPreviewSpan->getAttribute('aria-label') === 'Like') {
                $pathSelectedPhoto = "/html/body/div[{$instaX}]/div[2]/div/article/div[2]/section[1]/span[1]/button";
                $elmSelectedPhoto = $this->driver->findElement(WebDriverBy::xpath($pathSelectedPhoto));
                $elmSelectedPhoto->sendKeys('1')->click();
            }

        } catch (\Exception $e) {
            return false;
        }

        sleep(2);
        // close selected photo btn
        $this->driver->findElement(WebDriverBy::xpath("/html/body/div[{$instaX}]/button[1]"))->sendKeys(1)->click();

        return true;
    }

    /**
     * Open photo preview
     *
     * @param $position
     */
    private function openPhotoPreview($position)
    {
        [$row, $col] = $this->findXY($position);
        $notFound = 0;
        try {
            $pathPhotoInGrid_1 = sprintf('//*[@id="react-root"]/section/main/div/div[3]/article/div[1]/div/div[%d]/div[%d]/a', $row, $col);
            $elmPhotoPreview = $this->driver->findElement(WebDriverBy::xpath($pathPhotoInGrid_1));
        } catch (\Exception $e) {
            $notFound++;
        }

        try {
            if (1 === $notFound) {
                $pathPhotoInGrid_2 = sprintf('//*[@id="react-root"]/section/main/div/div[2]/article/div[1]/div/div[%d]/div[%d]/a', $row, $col);
                $elmPhotoPreview = $this->driver->findElement(WebDriverBy::xpath($pathPhotoInGrid_2));

            }

            $elmPhotoPreview->sendKeys('1')->click();
        } catch (\Exception $e) {

        }


    }

    /**
     * Is private profile
     *
     * @return bool
     */
    public function isPrivateProfile(): bool
    {
        try {
            $isPrivateAccount = $this->driver->findElement(
                WebDriverBy::xpath('//*[@id="react-root"]/section/main/div/div/article/div[1]/div/h2')
            );
            return 'This Account is Private' === $isPrivateAccount->getText();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param int $position
     *
     * @return array
     */
    public function findXY(int $position): array
    {
        $set = [
            1 => [1, 1],
            2 => [1, 2],
            3 => [1, 3],
            4 => [2, 1],
            5 => [2, 2],
            6 => [2, 3],
            7 => [3, 1],
            8 => [3, 2],
            9 => [3, 3],
        ];

        return $set[$position] ?? $set[1];
    }

    public function grapAllProfile()
    {

    }

    /**
     * @param $message
     */
    private function log($message)
    {
        $message .= PHP_EOL;
        print($message);
        flush();
        ob_flush();
    }
}

// Limitation in instagram
// 6.30m - 52 likes
// 6.30m - 26 followers

// TODO: grab all followers
