#!/usr/bin/env python3
"""
Verify attendance: Compare selfie against stored face encoding from database
Returns: {"match": true/false, "distance": 0.XXX, "matching_threshold": 0.6, ...}
"""

import sys
import json
import warnings

# Suppress deprecation warnings
warnings.filterwarnings('ignore', category=DeprecationWarning)
warnings.filterwarnings('ignore')

import face_recognition
import numpy as np
from PIL import Image
import os

def verify_face(captured_image_path, stored_encoding_json):
    """
    Args:
        captured_image_path: Path to the selfie taken at attendance
        stored_encoding_json: JSON string of stored encoding (array of floats)
    
    Returns:
        dict with match result and metrics
    """
    result = {}
    
    try:
        # Log file info
        if not os.path.exists(captured_image_path):
            result['match'] = False
            result['error'] = f'Image file not found: {captured_image_path}'
            result['distance'] = 999.0
            return result
        
        # Load captured image
        img = Image.open(captured_image_path)
        if img.mode != 'RGB':
            img = img.convert('RGB')
        
        original_size = list(img.size)  # Convert to list for JSON
        img.thumbnail((600, 600))
        image_array = np.array(img)
        
        # Parse stored encoding first (validate early)
        try:
            stored_enc = json.loads(stored_encoding_json)
            stored_encoding = np.array(stored_enc, dtype=np.float64)
            if len(stored_encoding) != 128:
                result['match'] = False
                result['error'] = f'Invalid stored encoding length: {len(stored_encoding)} (expected 128)'
                result['distance'] = 999.0
                return result
        except json.JSONDecodeError as je:
            result['match'] = False
            result['error'] = f'Invalid JSON in stored encoding: {str(je)}'
            result['distance'] = 999.0
            return result
        except Exception as e:
            result['match'] = False
            result['error'] = f'Error parsing stored encoding: {str(e)}'
            result['distance'] = 999.0
            return result
        
        # Try face detection with HOG first, then CNN if needed
        captured_encodings = None
        face_locations = None
        detection_method = None
        
        for model in ['hog', 'cnn']:
            for upsample in [0, 1]:
                try:
                    face_locations = face_recognition.face_locations(image_array, model=model, number_of_times_to_upsample=upsample)
                    
                    if len(face_locations) > 0:
                        captured_encodings = face_recognition.face_encodings(image_array, face_locations)
                        if len(captured_encodings) > 0:
                            detection_method = f'{model}(upsample={upsample})'
                            break
                except:
                    continue
            
            if captured_encodings and len(captured_encodings) > 0:
                break
        
        if not face_locations or len(face_locations) == 0:
            result['match'] = False
            result['error'] = 'No face detected in captured image (tried HOG and CNN)'
            result['distance'] = 999.0
            result['image_size'] = original_size
            return result
        
        if not captured_encodings or len(captured_encodings) == 0:
            result['match'] = False
            result['error'] = 'Could not generate encoding from captured image'
            result['distance'] = 999.0
            result['face_count'] = int(len(face_locations))
            return result
        
        # Compare first captured face against stored encoding
        captured_encoding = captured_encodings[0]
        
        # Validate captured encoding
        if len(captured_encoding) != 128:
            result['match'] = False
            result['error'] = f'Invalid captured encoding length: {len(captured_encoding)} (expected 128)'
            result['distance'] = 999.0
            return result
        
        # Euclidean distance (lower = more similar)
        distance = float(np.linalg.norm(captured_encoding - stored_encoding))
        
        # Standard threshold is 0.6; below = match
        threshold = 0.6
        match = bool(distance < threshold)
        
        result['match'] = match
        result['distance'] = round(distance, 4)
        result['threshold'] = float(threshold)
        result['face_count_captured'] = int(len(face_locations))
        result['detection_method'] = detection_method
        result['image_size'] = list(original_size)  # Convert tuple to list for JSON serialization
        
        return result
        
    except Exception as e:
        result['match'] = False
        result['error'] = f'Unexpected error: {str(e)}'
        result['distance'] = 999.0
        return result

if __name__ == '__main__':
    if len(sys.argv) < 3:
        output = {'error': 'Usage: verify_attendance_face.py <image_path> <stored_encoding_json>'}
        print(json.dumps(output))
        sys.exit(1)
    
    image_path = sys.argv[1]
    stored_encoding = sys.argv[2]
    
    result = verify_face(image_path, stored_encoding)
    
    # Ensure all values are JSON-serializable (convert numpy types)
    def convert_to_serializable(obj):
        if isinstance(obj, np.integer):
            return int(obj)
        elif isinstance(obj, np.floating):
            return float(obj)
        elif isinstance(obj, np.ndarray):
            return obj.tolist()
        elif isinstance(obj, dict):
            return {k: convert_to_serializable(v) for k, v in obj.items()}
        elif isinstance(obj, (list, tuple)):
            return [convert_to_serializable(item) for item in obj]
        return obj
    
    result = convert_to_serializable(result)
    print(json.dumps(result))
