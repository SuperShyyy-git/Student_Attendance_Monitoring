import sys
import face_recognition
import json
import os
import numpy as np
from PIL import Image
import time

def main():
    start_time = time.time()
    output_data = {}

    if len(sys.argv) < 2:
        output_data["error"] = "No image path provided"
        print(json.dumps(output_data))
        sys.exit(1)

    image_path = sys.argv[1]
    full_path = os.path.normpath(image_path)

    if not os.path.exists(full_path):
        output_data["error"] = f"Image file does not exist: {full_path}"
        print(json.dumps(output_data))
        sys.exit(1)

    try:
        img = Image.open(full_path).convert("RGB")
        # keep a full-resolution copy and a resized copy for drawing
        full_img = img.copy()
        # Resize to smaller size for faster detection (600px max instead of 1200px)
        img.thumbnail((600, 600))
        image = np.array(img)

        # Try faster model first (hog), then fallback to stronger model (cnn) if needed; only try upsample 0 for speed
        models = ["hog", "cnn"]
        found = False
        debug_image_path = None
        diagnostics = {
            "image_size": full_img.size,
            "attempts": []
        }

        for model in models:
            # Only try upsample 0 for speed; if needed we can add fallback
            for upsample in (0,):
                attempt = {"model": model, "upsample": upsample}
                try:
                    face_locations = face_recognition.face_locations(image, model=model, number_of_times_to_upsample=upsample)
                    encodings = face_recognition.face_encodings(image, face_locations)
                    attempt["face_count"] = len(face_locations)
                except Exception as ex:
                    attempt["error"] = str(ex)
                    diagnostics["attempts"].append(attempt)
                    continue

                diagnostics["attempts"].append(attempt)

                if len(encodings) > 0:
                    output_data["encoding"] = encodings[0].tolist()
                    output_data["model"] = model
                    output_data["upsample"] = upsample
                    found = True

                    # Save debug image (draw boxes on the resized image copy)
                    try:
                        from PIL import ImageDraw
                        draw = ImageDraw.Draw(img)
                        for (top, right, bottom, left) in face_locations:
                            draw.rectangle(((left, top), (right, bottom)), outline=(255, 0, 0), width=4)

                        logs_dir = os.path.join(os.path.dirname(__file__), '..', 'logs')
                        logs_dir = os.path.normpath(logs_dir)
                        if not os.path.isdir(logs_dir):
                            os.makedirs(logs_dir, exist_ok=True)

                        debug_filename = 'detected_' + os.path.basename(full_path)
                        debug_image_path = os.path.join(logs_dir, debug_filename)
                        img.save(debug_image_path)
                        output_data["debug_image"] = debug_image_path
                    except Exception:
                        pass

                    break
            if found:
                break

        output_data["diagnostics"] = diagnostics
        
        # Add timing info
        elapsed = time.time() - start_time
        output_data["processing_time_sec"] = round(elapsed, 3)

        if not found:
            output_data["error"] = "No face found in the image"

    except Exception as e:
        output_data["error"] = str(e)

    print(json.dumps(output_data))

if __name__ == "__main__":
    main()
