import sys
import json
import numpy as np

try:
    import face_recognition
except ImportError:
    print(json.dumps({"success": False, "error": "face_recognition library not installed"}))
    sys.exit(1)

if len(sys.argv) < 3:
    print(json.dumps({"success": False, "error": "Usage: identify_face.py <image_path> <encodings_file>"}))
    sys.exit(1)

image_path = sys.argv[1]
encodings_file = sys.argv[2]

try:
    # Load the image
    image = face_recognition.load_image_file(image_path)
    
    # Find faces in the image
    face_locations = face_recognition.face_locations(image)
    
    if len(face_locations) == 0:
        print(json.dumps({"success": False, "error": "No face detected in image"}))
        sys.exit(0)
    
    # Get encoding of the detected face
    face_encodings = face_recognition.face_encodings(image, face_locations)
    
    if len(face_encodings) == 0:
        print(json.dumps({"success": False, "error": "Could not encode detected face"}))
        sys.exit(0)
    
    unknown_encoding = face_encodings[0]
    
    # Read encodings from file
    with open(encodings_file, 'r', encoding='utf-8') as f:
        known_data = json.load(f)
    
    best_match_id = None
    best_distance = 0.6  # Threshold
    
    for student_id, encoding_list in known_data.items():
        known_encoding = np.array(encoding_list)
        distance = face_recognition.face_distance([known_encoding], unknown_encoding)[0]
        
        if distance < best_distance:
            best_distance = distance
            best_match_id = student_id
    
    if best_match_id:
        print(json.dumps({
            "success": True, 
            "student_id": int(best_match_id), 
            "distance": float(best_distance)
        }))
    else:
        print(json.dumps({"success": False, "error": "Face not recognized"}))
        
except Exception as e:
    print(json.dumps({"success": False, "error": str(e)}))
