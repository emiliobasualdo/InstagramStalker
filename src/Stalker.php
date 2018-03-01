<?php
/**
 * Created by PhpStorm.
 * User: pilo
 * Date: 2/21/18
 * Time: 9:09 PM
 */
namespace InstagramStalker;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/PHPTerminalProgressBar-master/PHPTerminalProgressBar.php';



use InstagramAPI\Instagram;
use InstagramAPI\Signatures;
use PHPTerminalProgressBar;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;


class Stalker {

    private $username;
    private $password;
    private $t_username;

    private $t_id;
    private $igDebug = false;
    private $igTruncatedDebug = false;
    private $ig;
    private $rankToken;

    private $hits = array();

    private $debug;
    private $processDebug = false;
    private $pb;

    const SLEEPTIME      = 5; // mgp25 suggests a minimum of 5 seconds between requests. Play at your own risk.
    const FILESDIR       = __DIR__."/../Files/";
    const CACHEDIR       = self::FILESDIR."Cache/";
    const HITSDIR        = self::FILESDIR."Hits/";
    const FACEREFDIR     = self::FILESDIR."FaceReferences/";
    const PYTHONDIR      = __DIR__."/python/";
    const PYENCODING     = self::PYTHONDIR."faceEconding.py";
    const PYRECOGNITION  = self::PYTHONDIR."faceRecognition.py";
    const FPS            = 3; // frames per second that will be chopped form a story in case it is a video
    const IMAGEQUALITY   = 3;
    const TOLERANCE      = 0.55; //face_recognition tolerance, lower numbers make face comparisons more strict
    const FILEDATEDORMAT = "d-m-y";
    const PBFORMAT       = "[:bar] :current/:total - :optional -:percent% - Elapsed::elapseds - ETA::etas";

    /**
     * Stalker constructor.
     * We validate arguments to check for null values
     * @param $arguments array of strings.
     */
    public function __construct($arguments){
        $this->debugger("Welcome to Instagram Stalker");
        $this->debugger("Verifying the given information...");

        for($i = 0; $i < count($arguments)-1; $i++)
            if (is_null($arguments[$i]))
                throw new \InvalidArgumentException("Arguments can not be null.");

        $this->username =  $arguments[0];
        $this->password =  $arguments[1];
        $this->t_username =  $arguments[2];
        $this->debug = $arguments[3]== "true" ? true: false;

        $this->checks();
        $this->encodeFaces();

        $this->ig = new Instagram($this->igDebug,$this->igTruncatedDebug);
        $this->login();
        $this->t_id = $this->ig->people->getUserIdForName($this->t_username);
        $this->rankToken = Signatures::generateUUID();
    }

    /**
     * The progress bar takes care of debugging,
     * If you don't like it, re-code the debugger() method.
     * So that we analise the target's story as well:  $friends[$this->t_username] = $this->t_id;
     *
     */
    public function start(){
        //We start
        $this->pb = new PHPTerminalProgressBar(1, self::PBFORMAT);
        $this->debugger("Getting intersection of friends...");
        $friends[$this->t_username] = $this->t_id; //so that we analise the target's story as well
        //We search for friends intersection
        $friends += $this->getfriends();
        $this->pb->total = count($friends);
        $this->debugger("Found ".$this->pb->total." friends in the intersection...");
        $this->analise($friends);
        $this->debugger($this->summary());
    }

    private function login(){
        try {
            $this->ig->login($this->username, $this->password);
        } catch (\Exception $e) {
            $this->processDebug('Something went wrong: '.$e->getMessage());
            exit(0);
        }
    }

    private function summary(){
        $message = "The stalking process has finished!\n";
        $message .= "This is a summary of the process:\n";
        $count = count($this->hits);
        if ($count == 0){
            $message .= "There where no appearances of target {$this->t_username} found\n";
        }else{
            $message .= "Target {$this->t_username} was found in the following stories:\n";
            foreach ($this->hits as $hit){
                $username = $hit->getUser()->getUsername();
                $date= date("D d.m.y",$hit->getDeviceTimestamp());
                $link = $hit->getLink();
                $message .= "{$username} ; taken on: {$date} ; {$link}\n";
            }
            $message .= "Remember all appearances can be found in the Files/Hits folder\n";
        }
        return $message;
    }

    private function checks(){
        $this->makeFolders();
        $this->checkReferences();
    }

    private function makeFolders(){
        $today = date(self::FILEDATEDORMAT);
        $folders = array(
            self::CACHEDIR,
            self::HITSDIR."{$this->t_username}/{$today}/",
            self::FACEREFDIR."{$this->t_username}/Faces/",
            self::FACEREFDIR."{$this->t_username}/Encodings/");
        foreach ($folders as $folder)
            $this->makeFolder($folder);
    }

    private function makeFolder($dir){
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private function checkReferences(){
        if ($this->is_dir_empty(self::FACEREFDIR."{$this->t_username}"))
            throw new FileNotFoundException("No face references where found for the target");
    }

    /**
     * For simplification and faster processing we encode known faces before
     * even starting
     */
    private function encodeFaces(){
        $encodingsProgram = self::PYENCODING;
        $facesDir         = self::FACEREFDIR."{$this->t_username}/Faces/";
        $encodingsDir     = self::FACEREFDIR."{$this->t_username}/Encodings/";
        exec("python3 {$encodingsProgram} {$facesDir} {$encodingsDir}");
    }

    private function is_dir_empty($dir) {
        if (!is_readable($dir))
            return NULL;
        return (count(scandir($dir)) == 2);
    }

    private function getFriends(){
        $followings = array();
        $followers = array();
        try {
            $maxId = null;
            do {
                $response = $this->ig->people->getFollowing($this->t_id, $this->rankToken,null, $maxId);
                $users = $response->getUsers();
                foreach ($users as $user)
                    $followings[$user->getUsername()] =  $user->getPk();
                $maxId = $response->getNextMaxId();
                sleep(self::SLEEPTIME);
            } while ($maxId !== null); // Must use "!==" for comparison instead of "!=".
        } catch (\Exception $e) {
            $this->processDebug('Something went wrong: '.$e->getMessage());
        }
        try {
            $maxId = null;
            do {
                $response = $this->ig->people->getFollowers($this->t_id, $this->rankToken,null, $maxId);
                $users = $response->getUsers();
                foreach ($users as $user)
                    $followers[$user->getUsername()] =  $user->getPk();
                $maxId = $response->getNextMaxId();
                sleep(self::SLEEPTIME);
            } while ($maxId !== null);
        } catch (\Exception $e) {
            $this->processDebug('Something went wrong: '.$e->getMessage());
        }
        return array_intersect($followings,$followers);
    }

    /**
     * @param $users
     * As said before, mgp25 from the php Instagram API suggests
     * 5 seconds between each request. As the face detection process
     * can be slow with videos(many images) but fast with single images
     * we count the time taken to do so sleep enough time for the 5
     * seconds to complete: sleep(self::SLEEPTIME - $timeTaken).
     * Again, play with the sleep value at your own risk
     * shorter periods could en up in your account being blocked.
     */
    private function analise($users){
        $this->debugger("Finding stories...");
        foreach ($users as $username => $userPk){
            $this->pb->tick($username);
            try{
                $userStories = $this->ig->story->getUserStoryFeed($userPk)->getReel();
                $time_start = microtime(true);
                if (!is_null($userStories)) {
                    $this->processDebug("Story/ies found on " . $username);
                    foreach ($userStories->getItems() as $story) {
                        if ($this->find($story)) {
                            $this->debugger("Found appearance in " . $username . "`s story...");
                            array_push($this->hits, $story);
                        }
                    }
                }else
                    $this->processDebug("No story found on ".$username);
                $timeTaken = microtime(true) - $time_start;
                if ($timeTaken < 5)
                    sleep(self::SLEEPTIME - $timeTaken);
            }catch (\Exception $e){
                $this->processDebug('Something went wrong: '.$e->getMessage());
            }
        }
    }

    /**
     * @param $story
     * @return bool
     * If the known face is found in the story we want to keep
     * the story, so we move it to a different folder.
     * In any case, we clean the cache.
     */
    private function find($story){
        $filepath = $this->save($story);
        $found = $this->identifyFace(self::FACEREFDIR."{$this->t_username}/Encodings/", self::CACHEDIR);
        // If found, we want to save the story in a different folder
        if ($found){
            $today = date(self::FILEDATEDORMAT);
            $this->moveTo($filepath, self::HITSDIR."{$this->t_username}/{$today}/");
        }
        // We clean the cache
        $this->wipeDirectory(self::CACHEDIR);
        return $found;
    }

    /**
     * @param $story
     * @return bool|string
     * If the story is a video we need to cut the it
     * into FPS/s image files so that face_recognition
     * can find the faces.
     * Python code could be written to avoid this step and
     * the ffmpeg dependency.
     */
    private function save($story){
        $mediaType = $story->getMediaType();
        $versions = null;
        $ext = null;
        switch ($mediaType) {
            case 1:
                $versions = $story->getImageVersions2()->getCandidates();
                $ext = ".jpg";
                $this->processDebug("Image found");
                break;
            case 2:
                $versions = $story->getVideoVersions();
                $ext = ".mp4";
                $this->processDebug("Video found");
                break;
            default:
                $this->processDebug("Unkown media type $mediaType");
                return false;
        }
        $url = $this->getLargest($versions);

        $name = $story->getUser()->getUsername();
        $pk = $story->getPk();
        $filepath = self::CACHEDIR.$name."_".$pk.$ext;

        $this->processDebug("Saving story to cache");
        file_put_contents($filepath, fopen($url, 'r'));

        if ($mediaType == 2){
            $this->videoToImages($filepath);
        }
        return $filepath;
    }

    private function moveTo($file, $to){
        $this->processDebug("Moving file");
        $newFilepath = $to.basename($file);
        rename($file,$newFilepath);
    }

    /**
     * @param $filepath
     * Image quiality and fps are both parameters you can
     * play without any risk(Instagram), you will though
     * slower the entire recognition process.
     * ageitgey at face_recognition suggests some values for
     * image quality and faster processing in videos.
     */
    private function videoToImages($filepath){
        $this->processDebug("Breaking video to images");
        $fps = self::FPS;
        $quality = self::IMAGEQUALITY;
        $outpath = self::CACHEDIR."/out%d.jpg";
        $command = "ffmpeg -i {$filepath} -vf fps={$fps} -q:v {$quality} {$outpath} 2>&1";
        exec($command);
    }

    /**
     * @param $versions
     * @return string
     * When requesting feed, such as stories, Instagram
     * return many possible sizes depending on your usage.
     * I haven't played with the sized to see which give
     * faster/more precise results
     */
    private function getLargest($versions){
        $height = 0;
        $url = "";
        foreach ($versions as $version){
            if ($version->getHeight() > $height){
                $height = $version->getHeight();
                $url = $version->getUrl();
            }
        }
        return $url;
    }

    /**
     * @param $encodingsDir
     * @param $imagesDir
     * @return bool
     * If run with python3 multi-core processing will make
     * the entire process nearly 4x faster. ageitgey explains
     * a bit in his repo
     */
    private function identifyFace($encodingsDir, $imagesDir){
        $recognitionProgram = self::PYRECOGNITION;
        $tolerance = self::TOLERANCE;
        exec("python3 {$recognitionProgram} {$tolerance} {$encodingsDir} {$imagesDir}" , $out, $return);
        return !$return;
    }

    private function wipeDirectory($path){
        $this->processDebug("Wiping entire directory");
        $files = glob($path.'*');
        foreach($files as $file){
            if(is_file($file))
                unlink($file);
        }
    }

    /**
     * @param $notification
     * So that the user can know whats happening
     */
    private function debugger($notification){
        if ($this->debug)
            $this->pb->interupt($notification);
    }

    /**
     * @param $notification
     * For the developer to know whats happening
     */
    private function processDebug($notification){
        if ($this->processDebug){
            $this->pb->interupt($notification);
        }
    }

}
