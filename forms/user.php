<?php
/* error_5 error al registrar usuario e intentar agregar login */
include_once 'forms/Dbconnection.php';
include_once 'generatecode.php';

class login extends DB
{
    private $nombre;
    private $rol;
    private $username;
    private $borrado;
    private $userid;
    private $unidatausercode; /* codigo de usuario existente */

    private function newcodeuser(){
        $code = new unicodeuser();
        $newgeneratecode = $code->generatecode($code->generatelong());
        return $newgeneratecode;
    }

    private function findreferido($unicode){
        $referido = $this->connect()->prepare('SELECT * FROM `usuarios` WHERE Codigo_usuario = ?');
        $referido->execute([$unicode]);
        return $referido;
    }

    private function testcode($nickname){
        $tempresult = $this->newcodeuser();
        $newcode = $nickname . '-' . $tempresult;
        $generatecode_exist = $this->findreferido($newcode);
        if ($generatecode_exist->rowCount()) {
            $this->testcode($nickname); /* se vuelve a llamar hasta encontrar un codigo que no este en uso */
        }else{
            return $newcode; /* nuevo codigo de usuario */
        }
    }
    
    public function adduser(
        $codigo,
        $user,
        $password,
        $Nombre,
        $Apellido,
        $nickname,
        $FechaN,
        $sexo,
        $Correo_electronico,
        $Telefono
    ) {
        $md5pass = md5($password);
       
        $newcodeuser = $this->testcode($nickname);

        $exist = $this->connect()->prepare('SELECT * FROM login WHERE User_login = :user AND password_login = :pass LIMIT 1');
        $exist->execute(['user' => $user, 'pass' => $md5pass]);

        if ($exist->rowCount()) {
            return "El Usuario que ingresaste ya esta siendo usado";
        } else {

            if ($codigo != ""){
                /* si encuentra al usuario */
                $resultado = $this->findreferido($codigo);
                if ($resultado->rowCount()) {
                    $idreferido = 0;
                    foreach ($resultado as $currentUser) {
                        $idreferido = $currentUser['idUsuario'];
                    }

                    $query = $this->connect()->prepare('INSERT INTO usuarios(`idUsuario`, `nickname_usuario`, `Nombre_usuario`, `ApellidoP_usuario`, `ApellidoM_usuario`, `FechaN_usuario`, `Genero_usuario`, `Telefono_usuario`, `Correo_electronico`, `NombreFotoPF_usuario`, `FB_usuario`, `YT_usuairo`, `Twitch_usuario`, `Twitter_usuario`, `IG_usuario`, `Monedas_usuario`, `Codigo_usuario`, `Rol_usuario`, `Borrado`)  
        VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $query->execute([
                        NULL, $nickname, $Nombre, $Apellido, NULL, $FechaN, $sexo, $Telefono, $Correo_electronico,
                        'sinfoto.jpg', NULL, NULL, NULL, NULL, NULL, NULL, $newcodeuser, '2', '0'
                    ]);

                    $getid = $this->connect()->prepare('SELECT idUsuario FROM usuarios ORDER BY idUsuario DESC LIMIT 1;');
                    $getid->execute();
                    $id = 0;

                    if ($query->rowCount()) {
                        foreach ($getid as $currentUser) {
                            $id = $currentUser['idUsuario'];
                        }
                        $login = $this->connect()->prepare('INSERT INTO `login`(`idLogin`, `User_login`, `password_login`, `fk_usuario`) 
        VALUES (?,?,?,?)');
                        $login->execute([
                            NULL, $user, $md5pass, $id
                        ]);
                    } else {
                        return 'error_5';
                    }


                    $addreferido  = $this->connect()->prepare('INSERT INTO `referidos`(`idReferido`, `usuario_refirio`, `usuario_referido`) 
                VALUES (?,?,?)');

                    $addreferido->execute([
                        NULL, $idreferido, $id
                    ]);

                    return "Usuario Agregado"; /* Usuario Agregado con referencia */
                } else {
                    /* si no encuentra al usuario */
                    return "No existe el codigo de referido que a ingresado";
                }
            } else {
                $query = $this->connect()->prepare('INSERT INTO usuarios(`idUsuario`, `nickname_usuario`, `Nombre_usuario`, `ApellidoP_usuario`, `ApellidoM_usuario`, `FechaN_usuario`, `Genero_usuario`, `Telefono_usuario`, `Correo_electronico`, `NombreFotoPF_usuario`, `FB_usuario`, `YT_usuairo`, `Twitch_usuario`, `Twitter_usuario`, `IG_usuario`, `Monedas_usuario`, `Codigo_usuario`, `Rol_usuario`, `Borrado`)  
        VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $query->execute([
                    NULL, $nickname, $Nombre, $Apellido, NULL, $FechaN, $sexo, $Telefono, $Correo_electronico,
                    'sinfoto.jpg', NULL, NULL, NULL, NULL, NULL, NULL, $newcodeuser, '2', '0'
                ]);

                $getid = $this->connect()->prepare('SELECT idUsuario FROM usuarios ORDER BY idUsuario DESC LIMIT 1;');
                $getid->execute();
                $id = 0;

                if ($query->rowCount()) {
                    foreach ($getid as $currentUser) {
                        $id = $currentUser['idUsuario'];
                    }
                    $login = $this->connect()->prepare('INSERT INTO `login`(`idLogin`, `User_login`, `password_login`, `fk_usuario`) 
        VALUES (?,?,?,?)');
                    $login->execute([
                        NULL, $user, $md5pass, $id
                    ]);

                    return "Usuario Agregado"; /* Usuario Agregado sin referencia */
                } else {
                    return 'error_5';
                }
            }
        }
    }

    public function userExists($user, $pass)
    {
        $md5pass = md5($pass);

        $query = $this->connect()->prepare('SELECT * FROM login WHERE User_login = :user AND password_login = :pass');
        $query->execute(['user' => $user, 'pass' => $md5pass]);

        if ($query->rowCount()) {
            return true;
        } else {
            return false;
        }
    }

    public function setUserAndfk($user)
    {
        $fkuser = $this->connect()->prepare('SELECT * from login WHERE User_login = :user ');
        $fkuser->execute(['user' => $user]);

        foreach ($fkuser as $currentUser) {
            $this->username = $currentUser['User_login'];
            $this->userid = $currentUser['fk_usuario'];
        }

        $nameuser = $this->connect()->prepare('SELECT * from usuarios WHERE idUsuario = :fk');
        $nameuser->execute(['fk' => $this->userid]);


        foreach ($nameuser as $currentUser) {
            $this->nombre = $currentUser['nickname_usuario'];
            $this->rol = $currentUser['Rol_usuario'];
            $this->borrado = $currentUser['Borrado'];
            $this->unidatausercode = $currentUser['Codigo_usuario'];
        }
    }


    public function getnickname()
    { //sobre o apodo nombre de jugador 
        return $this->nombre;
    }

    public function getrol()
    { //id de rol para administrar modulos
        return $this->rol;
    }

    public function getusername()
    { //usuario para login 
        return $this->username;
    }

    public function getborrado()
    { // saber si esta borrado logicamente
        return $this->borrado;
    }

    public function getusercode()
    { // codigo de usuario
        return $this->unidatausercode;
    }

    public function getuserid()
    { // id de usuario despues de un login 
        return $this->userid;
    }
}
