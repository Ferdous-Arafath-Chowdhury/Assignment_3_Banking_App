<?php

namespace BankingApp;


class User
{
    public $name;
    public $email;
    public $password;
    public $balance;

    public function __construct($name, $email, $password)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->balance = 0;
    }
}    

class BankingApp
{
    private $usersFile = 'users.json';
    private $currentUser = null;

    public function __construct()
    {
        $this->loadUsers();
    }

    private function loadUsers()
    {
        if (file_exists($this->usersFile)) {
            $json = file_get_contents($this->usersFile);
            $this->users = json_decode($json, true);
        } else {
            $this->users = [];
        }
    }

    private function saveUsers()
    {
        $json = json_encode($this->users, JSON_PRETTY_PRINT);
        file_put_contents($this->usersFile, $json);
    }

    private function findUserByEmail($email)
    {
        foreach ($this->users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }
        return null;
    }

    private function loginUser()
    {
        echo "Enter your email: ";
        $email = readline();
        echo "Enter your password: ";
        $password = readline();

        $user = $this->findUserByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            $this->currentUser = $user;
            echo "Login successful!\n";
            return true;
        } else {
            echo "Login failed. Invalid email or password.\n";
            return false;
        }
    }

    private function registerUser()
    {
        echo "Enter your name: ";
        $name = readline();
        echo "Enter your email: ";
        $email = readline();
        echo "Enter your password: ";
        $password = readline();

        if ($this->findUserByEmail($email)) {
            echo "User with this email already exists.\n";
            return;
        }

        $user = new User($name, $email, $password);
        $this->users[] = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $user->password,
            'balance' => $user->balance,
        ];

        $this->saveUsers();
        echo "Registration successful!\n";
    }

    private function displayBalance()
    {
        // echo "Your current balance is: $this->currentUser[balance]\n";

        $balance = $this->currentUser['balance'];
      echo "Your current balance is: $balance\n";

    }

    private function deposit()
    {
        echo "Enter the amount to deposit: ";
        $amount = floatval(readline());

        if ($amount <= 0) {
            echo "Invalid amount.\n";
            return;
        }

        $this->currentUser['balance'] += $amount;
        $this->saveUsers();
        echo "Deposit successful!\n";
    }

    private function withdraw()
    {
        echo "Enter the amount to withdraw: ";
        $amount = floatval(readline());

        if ($amount <= 0) {
            echo "Invalid amount.\n";
            return;
        }

        if ($amount > $this->currentUser['balance']) {
            echo "Insufficient balance.\n";
            return;
        }

        $this->currentUser['balance'] -= $amount;
        $this->saveUsers();
        echo "Withdrawal successful!\n";
    }

    private function transfer()
    {
        echo "Enter recipient's email: ";
        $recipientEmail = readline();

        $recipient = $this->findUserByEmail($recipientEmail);

        if (!$recipient) {
            echo "Recipient not found.\n";
            return;
        }

        echo "Enter the amount to transfer: ";
        $amount = floatval(readline());

        if ($amount <= 0) {
            echo "Invalid amount.\n";
            return;
        }

        if ($amount > $this->currentUser['balance']) {
            echo "Insufficient balance.\n";
            return;
        }

        $this->currentUser['balance'] -= $amount;
        $recipient['balance'] += $amount;

        $this->saveUsers();
        echo "Transfer successful!\n";
    }

    public function run()
    {
        while (true) {
            echo "1. Login\n";
            echo "2. Register\n";
            echo "3. Exit\n";
            echo "Enter your choice: ";
            $choice = readline();

            switch ($choice) {
                case '1':
                    if (!$this->currentUser) {
                        $this->loginUser();
                    } else {
                        echo "You are already logged in.\n";
                    }
                    break;
                case '2':
                    $this->registerUser();
                    break;
                case '3':
                    echo "You are leaving the app!\n";
                    exit(0);
                default:
                    echo "Invalid choice. Please try again.\n";
            }

            while ($this->currentUser) {
                echo "\nLogged in as: {$this->currentUser['name']} ({$this->currentUser['email']})\n";
                echo "4. Check Balance\n";
                echo "5. Deposit\n";
                echo "6. Withdraw\n";
                echo "7. Transfer\n";
                echo "8. Logout\n";
                echo "Enter your choice: ";
                $choice = readline();

                switch ($choice) {
                    case '4':
                        $this->displayBalance();
                        break;
                    case '5':
                        $this->deposit();
                        break;
                    case '6':
                        $this->withdraw();
                        break;
                    case '7':
                        $this->transfer();
                        break;
                    case '8':
                        $this->currentUser = null;
                        echo "Logged out.\n";
                        break;
                    default:
                        echo "Invalid choice. Please try again.\n";
                }
            }
        }
    }
} 



class Admin extends User
{
    public function viewAllTransactions(array $allUsers)
    {
        // Initialize an array to store all transactions
        $allTransactions = [];

        // Iterate through all users
        foreach ($allUsers as $user) {
            if (isset($user['transactions'])) {
                // Add each user's transactions to the allTransactions array
                $allTransactions = array_merge($allTransactions, $user['transactions']);
            }
        }

        // Sort transactions by date or another relevant criterion
        // Example: Sorting by transaction date in descending order
        usort($allTransactions, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        // Display all transactions
        foreach ($allTransactions as $transaction) {
            echo "Transaction Date: {$transaction['date']}\n";
            echo "User: {$transaction['user']}\n";
            echo "Amount: {$transaction['amount']}\n";
            echo "Description: {$transaction['description']}\n";
            echo "------------------------\n";
        }
    }

    public function viewTransactionsByUser(array $allUsers, $userEmail)
    {
        // Initialize an array to store transactions by the specified user
        $userTransactions = [];

        // Find the user by email
        $foundUser = null;
        foreach ($allUsers as $user) {
            if ($user['email'] === $userEmail) {
                $foundUser = $user;
                break;
            }
        }

        // If the user is found, retrieve their transactions
        if ($foundUser && isset($foundUser['transactions'])) {
            $userTransactions = $foundUser['transactions'];

            // Sort transactions by date or another relevant criterion
            // Example: Sorting by transaction date in descending order
            usort($userTransactions, function ($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']);
            });

            // Display transactions for the specified user
            echo "Transactions for User: {$foundUser['name']} ({$foundUser['email']})\n";
            foreach ($userTransactions as $transaction) {
                echo "Transaction Date: {$transaction['date']}\n";
                echo "Amount: {$transaction['amount']}\n";
                echo "Description: {$transaction['description']}\n";
                echo "------------------------\n";
            }
        } else {
            echo "User not found or no transactions found for this user.\n";
        }
    }

    public function viewAllCustomers(array $allUsers)
    {
        // Initialize an array to store customer data
        $customers = [];

        // Filter out Admin users
        foreach ($allUsers as $user) {
            if (!$user instanceof Admin) {
                $customers[] = $user;
            }
        }

        // Display a list of all customers
        echo "List of All Customers:\n";
        foreach ($customers as $customer) {
            echo "Name: {$customer['name']}\n";
            echo "Email: {$customer['email']}\n";
            echo "------------------------\n";
        }
    }
}





$bankingApp = new BankingApp();
$bankingApp->run();
