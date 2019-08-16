<?php

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Symfony\Component\Yaml\Yaml;

/**
 * Class InstagramBotTest
 */
class InstagramBotTest extends \PHPUnit\Framework\TestCase
{
    private static $driver;
    private static $cred;

    /**
     * @var InstagramBot
     */
    private $instagramBot;

    public static function setUpBeforeClass()
    {
        // TODO: extract into file


        $yaml = Yaml::parse(file_get_contents('../etc/config.yml'));

        self::$cred['username'] = $yaml['username'];
        self::$cred['password'] = $yaml['password'];

        $host = 'http://localhost:4444/wd/hub';

        $userAgent = 'Mozilla/5.0 (Linux; Android 4.0.4; Galaxy Nexus Build/IMM76B) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.133 Mobile Safari/535.19';

        $options = new ChromeOptions();
        $options->addArguments(array(
            '--user-agent=' . $userAgent
        ));

        $caps = DesiredCapabilities::chrome();
        $caps->setCapability(ChromeOptions::CAPABILITY, $options);

        self::$driver = RemoteWebDriver::create($host, $caps);
    }

    protected function setUp()
    {
        $this->instagramBot = new InstagramBot(self::$driver, self::$cred);
    }

    public function testLogin()
    {
        $this->assertEquals(true, $this->instagramBot->loginPage());
    }

    /**
     * @depends testLogin
     * @throws \Facebook\WebDriver\Exception\NoSuchElementException
     */
    public function testIndex()
    {
        while (1) {
            try {
                $this->instagramBot->scenarioOne('vitaliy.pavlish', \InstagramBot::SOURCE_FOLLOWING);
            } catch (Exception $e) {

            }
            sleep(500);
        }
//        $this->instagramBot->scenarioOne('vitaliy.pavlish', \InstagramBot::SOURCE_FOLLOWERS);

        $this->assertEquals(1, 1);
//        java -Dwebdriver.chrome.driver="chromedriver.exe" -jar selenium-server-standalone-3.14.0.jar

    }

    /**
     * @depends testLogin
     */
    public function testGetCountFollowers()
    {

        $this->assertEquals(true, $this->instagramBot->openUserProfile('vitaliy.pavlish'));
        $this->assertEquals(true, is_int($this->instagramBot->getCountFollowers()));
        $this->assertNotEquals(InstagramBot::DEFAULT_COUNT_FOLLOWERS, $this->instagramBot->getCountFollowers());
    }

    /**
     * @depends testLogin
     */
    public function testOpenPopupFollowing()
    {
        $this->assertEquals(true, $this->instagramBot->openUserProfile('vitaliy.pavlish'));
        $this->assertEquals(true, $this->instagramBot->openPopupFollowing());

    }

    /**
     * @depends testLogin
     * @dataProvider openPopupFollowersDataProvider
     *
     * @param string $username
     */
    public function testOpenPopupFollowers($username)
    {
        $this->assertEquals(true, $this->instagramBot->openUserProfile($username));
        $this->assertEquals(true, $this->instagramBot->openPopupFollowers());
    }

    /**
     * @return array
     */
    public function openPopupFollowersDataProvider(): array
    {
        return [
            ['vitaliy.pavlish'],
            ['dmytrunets']
        ];
    }

    /**
     * @depends testLogin
     */
    public function testLoadAllFollowersInPopUp()
    {
        $this->assertEquals(true, $this->instagramBot->openUserProfile('vitaliy.pavlish'));
        $countFollowers = $this->instagramBot->getCountFollowers(\InstagramBot::SOURCE_FOLLOWERS);
        $this->assertEquals(true, is_int($countFollowers));
        $this->assertEquals(true, $this->instagramBot->openPopupFollowers());
        $this->assertEquals(true, $this->instagramBot->loadFullProfileListInPopUp($countFollowers));
        $this->assertEquals(true, $this->instagramBot->scrollToFirstItemInPopUp());
    }

    /**
     * @depends      testLogin
     * @dataProvider likeProfilesDataProvider
     *
     * @param $username
     */
    public function testLikeRandomPhotos(string $username)
    {
        $username = 'miakobchuk';
        $this->assertEquals(true, $this->instagramBot->openUserProfile($username));
        $this->assertEquals(true, $this->instagramBot->likeRandomPhotos(rand(2, 5)));
//        $this->assertEquals(true, $this->instagramBot->likeRandomPhotos(2));
    }

    /**
     * @depends      testLogin
     * @dataProvider privateProfileDataProvider
     *
     * @param $username
     * @param $result
     */
    public function testIsPrivateProfile($username, $result)
    {
        $this->assertEquals(true, $this->instagramBot->openUserProfile($username));
        $this->assertEquals($result, $this->instagramBot->isPrivateProfile());
    }

    /**
     * @return array
     */
    public function privateProfileDataProvider(): array
    {
        return [
            ['anya_gerashchenko', true],
            ['smolinska_alina', false]
        ];
    }

    /**
     * @return array
     */
    public function likeProfilesDataProvider(): array
    {
        return [
            ['smolinska_alina'],
            ['kate_gudz'],
            ['anya_gerashchenko']
        ];
    }

    /**
     * @param $result
     * @param $position
     * @dataProvider positionDataProvider
     */
    public function testFindXY($result, $position)
    {
        $this->assertEquals($result[0], $this->instagramBot->findXY($position)[0], 'Row ' . $position);
        $this->assertEquals($result[1], $this->instagramBot->findXY($position)[1], 'Col ' . $position);
    }

    /**
     * @return array
     */
    public function positionDataProvider(): array
    {
        return [
            [[1, 1], 1],
            [[1, 2], 2],
            [[1, 3], 3],
            [[2, 1], 4],
            [[2, 2], 5],
            [[2, 3], 6],
        ];
    }

    /**
     * @depends testLogin
     * @param string $username
     * @dataProvider likeProfilesDataProvider
     */
    public function testFollowFromPage($username)
    {
        $this->assertEquals(true, $this->instagramBot->openUserProfile($username));
        $this->assertEquals(true, $this->instagramBot->followFromPage());
        sleep(2);
        $this->assertEquals(true, $this->instagramBot->unFollowFromPage());
    }

    /**
     * @depends      testLogin
     * @depends      testLikeRandomPhotos
     * @depends      testFollowFromPage
     * @dataProvider likeProfilesDataProvider
     */
    public function testFollowAndLike()
    {
        $this->assertEquals(1, 1);
    }

    /**
     * @depends      testLogin
     * @depends      testLikeRandomPhotos
     * @depends      testFollowFromPage
     * @dataProvider massProfileDataProvider
     */
    public function testFollowAndLikeMass()
    {
        $this->assertEquals(1, 1);
    }

    /**
     * @depends      testLogin
     *
     * @dataProvider massProfileDataProvider
     *
     * @param $username
     */
    public function testMassAction($username)
    {
        $this->assertEquals(true, is_string($username));
        $this->assertEquals(true, true);
        $this->testLikeRandomPhotos($username);
        $this->testFollowFromPage($username);
    }

    /**
     * @return array
     */
    public function massProfileDataProvider(): array
    {
        $content = file_get_contents(__DIR__. '/../db/dmytrunets/following.txt');
        $content = json_decode($content, true);
        return $content;
    }

    /**
     * @depends testLogin
     * @param $users
     * @dataProvider massUnFollowProfileDataProvider
     *
     * @return array
     */
    public function testMassUnfallowTest($users)
    {
        $this->assertEquals(true, $this->instagramBot->openUserProfile(self::$cred['username']));
        $countBefore = $this->instagramBot->getCountFollowers(\InstagramBot::SOURCE_FOLLOWING);

        foreach ($users as $username) {
            $this->instagramBot->openUserProfile($username);
            sleep(1);
            $this->instagramBot->unFollowFromPage();
        }

        $this->assertEquals(true, $this->instagramBot->openUserProfile(self::$cred['username']));
        $countAfter = $this->instagramBot->getCountFollowers(\InstagramBot::SOURCE_FOLLOWING);
        $this->assertEquals($countBefore - \InstagramBot::LIMIT_ACTION_UNFOLLOW, $countAfter, 'Instagram limit');

        sleep(60 * rand(1, 10));
    }

    /**
     * @return array
     */
    public function massUnFollowProfileDataProvider(): array
    {
        $content = file_get_contents(__DIR__. '/../db/vitaliy.pavlish/following.txt');
        $content = json_decode($content, true);
        $content = array_map(function($e) { return $e[0]; }, $content);

        $a = array_chunk($content, \InstagramBot::LIMIT_ACTION_UNFOLLOW);
        $a = array_map(function($e) { return [$e];}, $a);

        return $a;
    }
}
