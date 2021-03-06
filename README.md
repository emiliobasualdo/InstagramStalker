# InstagramStalker

A very simple php program for Instagram story stalking.
The program searches for appearances of a target user in stories, these stories are selected from the intersection of the followings and followers of the target.  
Once an appearance of the target is found, the story is saved to the current directory.  
The program requires the user to personally supply images of the target's face; for details on the recognition go to ageitgey/face_recognition wiki

The code was tested in OSx, never on Linux, though I don't see any problem with it.  
An example on how to use it as a module can be found in StalkerMain.php  

## Dependencies  
python3(python 3 is recommended as multi-core processing is allowed, if you wish to use python2, two lines of code must be changed)  
[ageitgey/face_recognition](https://github.com/ageitgey/face_recognition) and its dependencies  
ffmpeg  
[mgp25/InstagramApi](https://github.com/mgp25/Instagram-API) and its dependencies  

## Installation(OSX)  
`$ cd /path/to/InstagramStalker/src/`  
`$ curl -sS https://getcomposer.org/installer | php`  
`$ php composer.phar require mgp25/instagram-php`  
Continue to install [this dependencies](https://github.com/mgp25/Instagram-API/wiki/Dependencies) for the mgp25/instagram-php api to work  
`$ brew install ffmpeg`  
Install python if you don't already have it; I recommend [python3](https://www.python.org/downloads/mac-osx/) for the reasons mentioned before  
Continue to install the face_recognition api, follow [this instructions]    (https://github.com/ageitgey/face_recognition#installation)  

## Usage
Before even starting, your should put pictures containing only your target's face in /path/to/InstagramStalker/Files/FaceReferences/targetUsername/Faces/
There is an [example](https://github.com/emiliobasualdo/InstagramStalker/StalkerMain.php) on how to use the code.  


Please feel free to suggest any changes and criticise my work as it is one of my firsts.    
Happy Stalking  
