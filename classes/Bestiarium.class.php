<?php
class Bestiarium{
    // Propriétés
    private string $name;
    private int $hp;

    private int $damage;

    private string $races;

    private string $description;
    private string $images;

    private int $heads;



    // Constructeur

    public function __construct(string $name, int $hp, int $damage){
        $this->name = $name;
        $this->hp = $hp;
        $this->damage = $damage;
        
    }

    public function __toString() : string{
        return
        "Name : " . $this->name . 
        "Hp : " . $this->hp . 
        "Damage : " . $this->damage .
        "Description : " . $this->description
        ;

    }
    public function toArray(): array {
        return [
            'name' => $this->name,
            'hp' => $this->hp,
            'damage' => $this->damage,
            'description' => $this->description,
        ];
    }

    // Getters et Setters
    public function getName(): string{
        return $this->name;
    }
    public function setName(string $name): void{
        $this->name = $name;
    }   
    public function getHp(): int{
        return $this->hp;
    }
    public function setHp(int $hp): void{
        $this->hp = $hp;
    }
    public function getDamage(): int{ 
        return $this->damage;
    }
    public function setDamage(int $damage): void{
        $this->damage = $damage;
    }
    public function getDescription(): string{
        return $this->description;
    }
    public function setDescription(string $description): void{
        $this->description = $description;
    }


}