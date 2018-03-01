# InstagramStalker

A very simple php program for Instagram story stalking.
The program searches for appearances of a target user in stories, these stories are selected from the intersection of the followings and followers of the target.  
Once the an appearance of the target is found, the story is saved on the machine.  
The program requires the user to personally supply with images of the target's face; for details on the recognition go to ageitgey/face_recognition wiki

The code was tested in OSx, never on Linux, though I don't see any problem with it.  
It was intended for CLI usage as part of a cronjob but an example on how to use it as a module can be found in StalkerMain.php  

Dependencies  
python3(python 3 is recommended as multi-core processing is allowed, if you wish to use python2, two lines of code must be changed)  
[ageitgey/face_recognition](https://github.com/ageitgey/face_recognition) and its dependencies  
ffmpeg  
[mgp25/InstagramApi](https://github.com/mgp25/Instagram-API) and its dependencies  

Please feel free to suggest any changes and criticise my work as it is one of my firsts.  
Happy Stalking  

BTC Wallet: 14LQoDdW7BG6tqAG6tmkEx4G4LvqJQQJBb  
