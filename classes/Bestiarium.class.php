<?php
class Bestiarium {
    private string $name;
    private int $hp;
    private int $damage;
    private int $defense;
    private int $heads;
    private string $description;
    private string $image;

    public function __construct(string $name, string $image) {
        $this->name = $name;
        $this->image = $image;
    }

    public function __toString(): string {
        return
        "Name : " . $this->name .
        " | Image : " . $this->image .
        " | Description : " . $this->description;
    }

    public function toArray(): array {
        return [
            'name' => $this->name,
            'hp' => $this->hp,
            'damage' => $this->damage,
            'defense' => $this->defense,
            'heads' => $this->heads,
            'description' => $this->description,
            'image' => $this->image,
        ];
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getHp(): int {
        return $this->hp;
    }

    public function setHp(int $hp): void {
        $this->hp = $hp;
    }

    public function getDamage(): int {
        return $this->damage;
    }

    public function setDamage(int $damage): void {
        $this->damage = $damage;
    }

    public function getDefense(): int {
        return $this->defense;
    }

    public function setDefense(int $defense): void {
        $this->defense = $defense;
    }

    public function getHeads(): int {
        return $this->heads;
    }

    public function setHeads(int $heads): void {
        $this->heads = $heads;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): void {
        $this->description = $description;
    }

    public function getImage(): string {
        return $this->image;
    }

    public function setImage(string $image): void {
        $this->image = $image;
    }
}
