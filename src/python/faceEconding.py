import face_recognition
import sys
import os
import pickle


faceFolder      = sys.argv[1]
encodingsFolder = sys.argv[2]

for subdir, dirs, files in os.walk(faceFolder):
    for file in files:
        if not (file.startswith(".")):
            facePath = faceFolder + file
            known_image = face_recognition.load_image_file(facePath)
            image_encoding = face_recognition.face_encodings(known_image)[0]

            encodingPath = encodingsFolder + file + ".obj"
            with open(encodingPath,'wb') as f:
                pickle.dump(image_encoding, f)
                f.close()
