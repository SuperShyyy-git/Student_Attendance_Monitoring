import face_recognition
import sys
import json
import os
import numpy as np

if len(sys.argv) != 3:
    print(json.dumps({"error": "Usage: python verify=<capture> <encoding>"}))
    sys.exit(1)

captured_path = os.path.normpath(sys.argv[1])
stored_encoding_json = sys.argv[2]

if not os.path.isfile(captured_path):
    print(json.dumps({"error": f"Captured image not found"}))
    sys.exit(1)

try:
    captured_img = face_recognition.load_image_file(captured_path)
    captured_encodings = face_recognition.face_encodings(captured_img)
    if not captured_encodings:
        print(json.dumps({"match": False, "reason": "No face found"}))
        sys.exit(0)

    captured_encoding = captured_encodings[0]
    stored_encoding = np.array(json.loads(stored_encoding_json))

    distance = face_recognition.face_distance([stored_encoding], captured_encoding)[0]
    threshold = 0.45
    match = distance < threshold

    print(json.dumps({
        "match": bool(match),
        "distance": float(distance),
        "threshold": threshold
    }))

except Exception as e:
    print(json.dumps({"error": str(e)}))
