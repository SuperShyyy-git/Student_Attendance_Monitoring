<!DOCTYPE html>
<html>

<head>
    <title>Section & Grade Level</title>
    <link rel="stylesheet" href="../CSS/sec_yr_level.css">

</head>

<body>

    <?php
    include __DIR__ . "/../config/db_connect.php";
    ?>
    <div class="header-bar"><button id="btn-logout" class="btn-logout">Logout</button></div>
    <style>
        .header-bar {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 10px 20px 0 20px;
        }

        .btn-logout {
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 18px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-left: 20px;
        }

        .btn-logout:hover {
            background: #b71c1c;
        }

        .logout-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .logout-modal-box {
            background: #fff;
            padding: 28px 32px;
            border-radius: 10px;
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.12);
            text-align: center;
            min-width: 320px;
        }

        .logout-modal-box h3 {
            margin-bottom: 18px;
        }

        .logout-modal-box .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 18px;
            margin-top: 18px;
        }

        .logout-modal-box button {
            padding: 8px 22px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
        }

        .logout-modal-box .btn-yes {
            background: #dc3545;
            color: #fff;
        }

        .logout-modal-box .btn-no {
            background: #e2e3e5;
            color: #333;
        }
    </style>




    <h2 class='table-title'>Section & Grade Level</h2>

    <!-- ADD SECTION BUTTON -->
    <button id="btn-open-add-section" class="btn-add-student">➕ Add Section</button>

    <hr>

    <!-- TABLE -->
    <table class="student-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Section</th>
                <th>Grade Level</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $query = "SELECT * FROM section_yrlevel ORDER BY id DESC";
            $result = $conn->query($query);

            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {

                    $id = (int) $row['id'];
                    $section_display = htmlspecialchars($row['section'], ENT_QUOTES);
                    $grade_display = htmlspecialchars($row['grade_level'], ENT_QUOTES);

                    $section_attr = htmlspecialchars($row['section'], ENT_QUOTES);
                    $grade_attr = htmlspecialchars($row['grade_level'], ENT_QUOTES);

                    echo "
                <tr>
                    <td>{$id}</td>
                    <td>{$section_display}</td>
                    <td>{$grade_display}</td>
                    <td>
                        <button 
                            type='button'
                            class='btn-edit'
                            data-id='{$id}'
                            data-section=\"{$section_attr}\"
                            data-grade=\"{$grade_attr}\"
                        >
                            <span class='icon'>✏️</span> Edit
                        </button>

                        <button 
                            type='button'
                            class='btn-delete'
                            data-id='{$id}'
                        >
                            <span class='icon'>⛔</span> Delete
                        </button>
                    </td>
                </tr>
                ";
                }
            } else {
                echo "<tr><td colspan='4' style='text-align:center;'>No data found.</td></tr>";
            }
            ?>
        </tbody>
    </table>


    <!-- ADD SECTION MODAL -->
    <div id="add-section-modal" class="modal-overlay hidden">
        <div class="modal-box">

            <h3>Add Section & Grade Level</h3>

            <form id="add-section-form" class="section-form">

                <label>Section</label>
                <input type="text" name="section" required>

                <label>Grade Level</label>
                <input type="text" name="grade_level" required>

                <div class="modal-buttons">
                    <button type="submit" class="btn-save-edit">Save</button>
                    <button type="button" id="btn-cancel-add-section" class="btn-cancel-edit">Cancel</button>
                </div>

            </form>

        </div>
    </div>



    <!-- EDIT MODAL -->
    <div id="edit-modal" class="modal-overlay hidden">
        <div class="modal-box">

            <h3>Edit Section & Grade Level</h3>

            <form id="edit-section-form">
                <input type="hidden" id="edit-id" name="id">

                <label>Section</label>
                <input type="text" id="edit-section" name="section" required>

                <label>Grade Level</label>
                <input type="text" id="edit-grade-level" name="grade_level" required>

                <div class="modal-buttons">
                    <button type="submit" class="btn-save-edit">Save</button>
                    <button type="button" id="btn-cancel-edit" class="btn-cancel-edit">Cancel</button>
                </div>
            </form>

        </div>
    </div>

    <script src="sec_yr_level.js"></script>

    <script src="sec_yr_level.js"></script>

</body>

</html>