import face_recognition
import sys
import os
import pickle
import concurrent.futures

def load_known_faces(encodingsFolder):
    known_faces = []
    for subdir, dirs, files in os.walk(encodingsFolder):
        for file in files:
            if not (file.startswith(".")):
                encodingPath = encodingsFolder + file
                with open(encodingPath,'rb') as f:
                    known_faces.append(pickle.load(f))
                    f.close()
    return known_faces

def compare_faces(unknown_face):
    return face_recognition.compare_faces(known_faces,unknown_face, tolerance)

def appears_in_image(imagesFolder):
    for subdir, dirs, files in os.walk(imagesFolder):
        for file in files:
            if  (not file.startswith(".")) and (file.endswith(".jpg") or file.endswith(".png")):
                imagePath = imagesFolder + file
                unknown_image = face_recognition.load_image_file(imagePath)
                unknown_faces_encoding = face_recognition.face_encodings(unknown_image)
                with concurrent.futures.ProcessPoolExecutor() as executor:
                    for unknown_face, result in zip(unknown_faces_encoding, executor.map(compare_faces, unknown_faces_encoding)):
                        if True in result:
                            sys.exit(0)
    sys.exit(1)

tolerance       = float(sys.argv[1])
encodingsFolder = sys.argv[2]
imagesFolder    = sys.argv[3]
known_faces = load_known_faces(encodingsFolder)
appears_in_image(imagesFolder)
