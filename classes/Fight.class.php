<?php
class Fight{
    private string $result;
    private int $creature1;
    private int $creature2;
    
    public function __construct(string $result, int $creature1, int $creature2)
    {
        $this->result = $result;
        $this->creature1 = $creature1;
        $this->creature2 = $creature2;
    }
    public function getResult(): string{
        return $this->result;
    }
    public function getCreature1(): int{
        return $this->creature1;
    }
    public function getCreature2(): int{
        return $this->creature2;
    }
    

}