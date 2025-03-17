<?php
// Dans admin_action.php

if ($_POST['action'] === 'create_assign_module') {
    try {
        $conn->beginTransaction();

        
        
        $stmt->execute([
            ':nom' => $_POST['module_nom'],
            ':coefficient' => $_POST['coefficient'],
            ':type' => $_POST['type'],
            ':description' => $_POST['module_description']
        ]);

        $module_id = $conn->lastInsertId();
        $formateur_id = $_POST['formateur_id'];
        $branches = $_POST['branches'];

        // 2. Assigner le module aux branches sélectionnées
        $stmt = $conn->prepare("
            INSERT INTO modules (module_id, branche_id, formateur_id)
            VALUES (:module_id, :branche_id, :formateur_id)
        ");

        foreach ($branches as $branche_id) {
            $stmt->execute([
                ':module_id' => $module_id,
                ':branche_id' => $branche_id,
                ':formateur_id' => $formateur_id
            ]);

            // 3. Assigner automatiquement aux groupes de cette branche
            $groupStmt = $conn->prepare("
                INSERT INTO modules_groupes (module_id, groupe_id, formateur_id)
                SELECT :module_id, id_g, :formateur_id
                FROM groupes
                WHERE id_branche = :branche_id
            ");
            
            $groupStmt->execute([
                ':module_id' => $module_id,
                ':formateur_id' => $formateur_id,
                ':branche_id' => $branche_id
            ]);
        }

        $conn->commit();
        $_SESSION['message'] = "Module créé et assigné avec succès";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Erreur lors de la création du module : " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header('Location: admin.php');
    exit();
}
?>