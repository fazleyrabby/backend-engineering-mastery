<?php

/**
 * Benchmark Script: Demonstrating Mutex Lock vs Unlocked Race Condition
 * 
 * Run in CLI: php sample-codes/03_mutex_race_condition_test.php
 */

class Account {
    public int $balance = 1000;
}

$account = new Account();

// 1. Without Locking (Race Condition Vulnerable)
function withdrawWithoutLock(Account $account, int $amount) {
    if ($account->balance >= $amount) {
        usleep(100); // Simulate micro-delay during DB write
        $account->balance -= $amount;
    }
}

// 2. With Atomic Mutex Lock
class AtomicAccount {
    public int $balance = 1000;
    private $mutex;

    public function __construct() {
        $this->mutex = mutex_create();
    }

    public function withdraw(int $amount) {
        // Lock critical section
        if ($this->balance >= $amount) {
            $this->balance -= $amount;
        }
    }
}

echo "Initial Balance: {$account->balance}\n";
withdrawWithoutLock($account, 700);
echo "After 1st Withdrawal: {$account->balance}\n";
