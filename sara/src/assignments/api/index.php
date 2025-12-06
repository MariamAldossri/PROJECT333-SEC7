<?php
/**
 * Assignment Management API
 */

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../common/db.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$resource = isset($_GET['resource']) ? $_GET['resource'] : '';

// Parse input
$input = json_decode(file_get_contents("php://input"), true);

// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

function getAllAssignments($db) {
    try {
        $query = "SELECT * FROM assignments ORDER BY due_date ASC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode files JSON
        foreach ($assignments as &$assignment) {
            if (isset($assignment['files'])) {
                $assignment['files'] = json_decode($assignment['files']);
            }
        }
        
        echo json_encode($assignments);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

function getAssignmentById($db, $id) {
    try {
        $query = "SELECT * FROM assignments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($assignment) {
            if (isset($assignment['files'])) {
                $assignment['files'] = json_decode($assignment['files']);
            }
            echo json_encode($assignment);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Assignment not found."]);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

function createAssignment($db, $data) {
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
        return;
    }

    try {
        $query = "INSERT INTO assignments (title, description, due_date, files) VALUES (:title, :description, :due_date, :files)";
        $stmt = $db->prepare($query);

        $title = htmlspecialchars(strip_tags($data['title']));
        $description = htmlspecialchars(strip_tags($data['description']));
        $due_date = htmlspecialchars(strip_tags($data['due_date']));
        $files = isset($data['files']) ? json_encode($data['files']) : json_encode([]);

        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":due_date", $due_date);
        $stmt->bindParam(":files", $files);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(["message" => "Assignment created.", "id" => $db->lastInsertId()]);
        } else {
            http_response_code(503);
            echo json_encode(["message" => "Unable to create assignment."]);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

function updateAssignment($db, $data) {
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["message" => "ID is required for update."]);
        return;
    }

    try {
        // Build dynamic query
        $fields = [];
        $params = [':id' => $data['id']];

        if (isset($data['title'])) {
            $fields[] = "title = :title";
            $params[':title'] = htmlspecialchars(strip_tags($data['title']));
        }
        if (isset($data['description'])) {
            $fields[] = "description = :description";
            $params[':description'] = htmlspecialchars(strip_tags($data['description']));
        }
        if (isset($data['due_date'])) {
            $fields[] = "due_date = :due_date";
            $params[':due_date'] = htmlspecialchars(strip_tags($data['due_date']));
        }
        if (isset($data['files'])) {
            $fields[] = "files = :files";
            $params[':files'] = json_encode($data['files']);
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(["message" => "No fields to update."]);
            return;
        }

        $query = "UPDATE assignments SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $db->prepare($query);

        if ($stmt->execute($params)) {
             echo json_encode(["message" => "Assignment updated."]);
        } else {
             http_response_code(503);
             echo json_encode(["message" => "Unable to update assignment."]);
        }

    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

function deleteAssignment($db, $id) {
    try {
        $query = "DELETE FROM assignments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
             echo json_encode(["message" => "Assignment deleted."]);
        } else {
             http_response_code(503);
             echo json_encode(["message" => "Unable to delete assignment."]);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

function getCommentsByAssignment($db, $assignmentId) {
    try {
        $query = "SELECT * FROM comments WHERE assignment_id = :aid ORDER BY created_at ASC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":aid", $assignmentId);
        $stmt->execute();
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($comments);
    } catch(PDOException $e) {
         http_response_code(500);
         echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

function createComment($db, $data) {
     if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        http_response_code(400);
        echo json_encode(["message" => "Incomplete data."]);
        return;
    }

    try {
        $query = "INSERT INTO comments (assignment_id, author, text) VALUES (:aid, :author, :text)";
        $stmt = $db->prepare($query);

        $aid = htmlspecialchars(strip_tags($data['assignment_id']));
        $author = htmlspecialchars(strip_tags($data['author']));
        $text = htmlspecialchars(strip_tags($data['text']));

        $stmt->bindParam(":aid", $aid);
        $stmt->bindParam(":author", $author);
        $stmt->bindParam(":text", $text);

        if ($stmt->execute()) {
             http_response_code(201);
             echo json_encode(["message" => "Comment created.", "id" => $db->lastInsertId()]);
        } else {
             http_response_code(503);
             echo json_encode(["message" => "Unable to create comment."]);
        }

    } catch(PDOException $e) {
         http_response_code(500);
         echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}

function deleteComment($db, $id) {
     try {
        $query = "DELETE FROM comments WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
             echo json_encode(["message" => "Comment deleted."]);
        } else {
             http_response_code(503);
             echo json_encode(["message" => "Unable to delete comment."]);
        }
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . $e->getMessage()]);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

if ($resource === 'assignments') {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                getAssignmentById($db, $_GET['id']);
            } else {
                getAllAssignments($db);
            }
            break;
        case 'POST':
            createAssignment($db, $input);
            break;
        case 'PUT':
            updateAssignment($db, $input);
            break;
        case 'DELETE':
            if (isset($_GET['id'])) {
                deleteAssignment($db, $_GET['id']);
            } else {
                http_response_code(400);
                echo json_encode(["message" => "ID required for deletion."]);
            }
            break;
        default:
            http_response_code(405);
            echo json_encode(["message" => "Method not allowed."]);
            break;
    }
} elseif ($resource === 'comments') {
    switch ($method) {
        case 'GET':
             if (isset($_GET['assignment_id'])) {
                getCommentsByAssignment($db, $_GET['assignment_id']);
             } else {
                 http_response_code(400);
                 echo json_encode(["message" => "Assignment ID required."]);
             }
             break;
        case 'POST':
            createComment($db, $input);
            break;
        case 'DELETE':
             if (isset($_GET['id'])) {
                deleteComment($db, $_GET['id']);
             } else {
                 http_response_code(400);
                 echo json_encode(["message" => "ID required for deletion."]);
             }
             break;
        default:
             http_response_code(405);
             echo json_encode(["message" => "Method not allowed."]);
    }
} else {
    http_response_code(404);
    echo json_encode(["message" => "Resource not found."]);
}

?>
