<?php

namespace App\Models;

class MyClass
{
    private $create a user class with properties name;
    private $email;
    private $password;

    public function __construct($create a user class with properties name, $email, $password)
    {
        $this->create a user class with properties name = $create a user class with properties name;
        $this->email = $email;
        $this->password = $password;
    }
    
    // Getters
    public function getCreate a user class with properties name()
    {
        return $this->create a user class with properties name;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }


    // Setters
    public function setCreate a user class with properties name($create a user class with properties name)
    {
        $this->create a user class with properties name = $create a user class with properties name;
        return $this;
    }

    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }


}