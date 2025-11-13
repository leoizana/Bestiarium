<?php
class User{
    private string $username;
    private string $password;
    private string $email;

    public function __construct(string $username, string $email){
        $this->username = $username;
        $this->email = $email;
    }
    public function toArray(): array {
        return [
            'email' => $this->email,
            'username' => $this->username,
        ];
    }

    public function getUsername(): string{
        return $this->username;
    }

    public function setUsername(string $username): void{
        $this->username = $username;
    }

    public function getPassword(): string{
        return $this->password;
    }

    public function setPassword(string $password): void{
        $this->password = $password;
    }

    public function getEmail(): string{
        return $this->email;
    }

    public function setEmail(string $email): void{
        $this->email = $email;
    }
}