<?php
// validate_field.php
session_start();
include 'config.php'; 

$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');
$id = intval($_POST['id'] ?? 0); // for edit mode
$response = ['status' => false, 'message' => ''];

if($field && $value !== null){
    $escaped_value = mysqli_real_escape_string($conn, $value);
    
    // Base uniqueness query template
    $uniqueness_query_template = "SELECT id FROM domain_list WHERE %s = '%s'";
    if ($id > 0) {
        $uniqueness_query_template .= " AND id != '$id'";
    }

    switch($field){
        case 'customer_name':
            if($value === ''){
                $response['message'] = "Customer Name cannot be empty!";
            } else if(strlen($value) < 3){
                $response['message'] = "Customer Name must be at least 3 characters!";
            } else {
                // Check duplicates
                $query = sprintf($uniqueness_query_template, 'customer_name', $escaped_value);
                $q = mysqli_query($conn, $query);
                if(mysqli_num_rows($q) > 0){
                    $response['message'] = "Customer Name already exists!";
                    $response['status'] = false;
                } else {
                    $response['status'] = true;
                }
            }
            break;

        case 'phone':
            if(!preg_match('/^[0-9]{10}$/', $value)){
                $response['message'] = "Phone must be exactly 10 digits!";
            } else {
                $query = sprintf($uniqueness_query_template, 'phone', $escaped_value);
                $q = mysqli_query($conn, $query);
                if(mysqli_num_rows($q) > 0){
                    $response['message'] = "Phone already exists!";
                    $response['status'] = false;
                } else {
                    $response['status'] = true;
                }
            }
            break;

        case 'whatsapp':
            if($value !== '' && !preg_match('/^[0-9]{10}$/', $value)){
                $response['message'] = "WhatsApp must be exactly 10 digits!";
                $response['status'] = false;
            } else if($value !== ''){
                $query = sprintf($uniqueness_query_template, 'whatsapp', $escaped_value);
                $q = mysqli_query($conn, $query);
                if(mysqli_num_rows($q) > 0){
                    $response['message'] = "WhatsApp already exists!";
                    $response['status'] = false;
                } else {
                    $response['status'] = true;
                }
            } else {
                $response['status'] = true; // empty WhatsApp allowed
            }
            break;

        case 'email':
            if($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)){
                $response['message'] = "Invalid email format!";
                $response['status'] = false;
            } else if($value !== ''){
                $query = sprintf($uniqueness_query_template, 'email', $escaped_value);
                $q = mysqli_query($conn, $query);
                if(mysqli_num_rows($q) > 0){
                    $response['message'] = "Email already exists!";
                    $response['status'] = false;
                } else {
                    $response['status'] = true;
                }
            } else {
                $response['status'] = true; // empty email allowed
            }
            break;

        case 'domain_name':
            if($value === ''){
                $response['message'] = "Domain Name cannot be empty!";
            } else {
                $query = sprintf($uniqueness_query_template, 'domain_name', $escaped_value);
                $q = mysqli_query($conn, $query);
                if(mysqli_num_rows($q) > 0){
                    $response['message'] = "Domain Name already exists!";
                    $response['status'] = false;
                } else {
                    $response['status'] = true;
                }
            }
            break;

        default:
            $response['status'] = true; // for any other fields
            break;
    }
} else {
    $response['status'] = true; // optional fields
}

echo json_encode($response);
