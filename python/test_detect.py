import sys
from PIL import Image
import numpy as np
import face_recognition

if len(sys.argv) < 2:
    print('Usage: test_detect.py <image_path>')
    sys.exit(1)

path = sys.argv[1]
print('Image:', path)
img = Image.open(path).convert('RGB')
print('Size:', img.size)
arr = np.array(img)
for model in ('hog','cnn'):
    try:
        locs = face_recognition.face_locations(arr, model=model)
        encs = face_recognition.face_encodings(arr, locs)
        print(f"model={model} -> faces={len(locs)}, encodings={len(encs)}")
    except Exception as e:
        print(f"model={model} -> ERROR: {e}")
