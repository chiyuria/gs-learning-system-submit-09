<?php
function login_success(array $user): void
{
    session_regenerate_id(true);

    $_SESSION["chk_ssid"] = session_id();

    $_SESSION["user"] = [
        "id"         => (int)($user["id"] ?? 0),
        "role"       => $user["role"] ?? null,
        "name"       => $user["name"] ?? null,
        "login_code" => $user["login_code"] ?? null,
    ];
}

function require_login(): void
{
    if (
        empty($_SESSION["chk_ssid"]) ||
        $_SESSION["chk_ssid"] !== session_id() ||
        empty($_SESSION["user"]["id"])
    ) {
        redirect("index.php");
        exit();
    }
}

function require_role(array $allowedRoles): void
{
    require_login();

    $role = $_SESSION["user"]["role"] ?? null;
    if (!$role || !in_array($role, $allowedRoles, true)) {

        redirect("index.php");
        exit();
    }
}

function require_student(): void
{
    require_role(["student"]);
}

function require_teacher(): void
{
    require_role(["teacher", "admin"]);
}

function require_admin(): void
{
    require_role(["admin"]);
}
