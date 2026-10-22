-- ============================================================
-- Gamified College Learning App - Database
-- ============================================================

CREATE DATABASE IF NOT EXISTS gamified_learning;
USE gamified_learning;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    total_xp INT DEFAULT 0
);

-- Admin Table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Languages Table
CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) DEFAULT '💻',
    description TEXT,
    color VARCHAR(20) DEFAULT '#4A90E2'
);

-- Levels Table
CREATE TABLE IF NOT EXISTS levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level_number INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    xp_reward INT DEFAULT 100
);

-- Lessons Table
CREATE TABLE IF NOT EXISTS lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT NOT NULL,
    level_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    theory TEXT NOT NULL,
    syntax_example TEXT,
    code_example TEXT,
    practice_problem TEXT,
    hint TEXT,
    solution TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE
);

-- Progress Table
CREATE TABLE IF NOT EXISTS progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    language_id INT NOT NULL,
    level_id INT NOT NULL,
    completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME,
    xp_earned INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    FOREIGN KEY (level_id) REFERENCES levels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_progress (user_id, language_id, level_id)
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- Admin user (Username: Admin@123, Password: Sss@2005)
INSERT INTO admin (username, password) VALUES 
('Admin@123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
-- Note: The password hash above is for 'Sss@2005' generated with PASSWORD_DEFAULT

-- Languages
INSERT INTO languages (name, slug, icon, description, color) VALUES
('C', 'c', '🔵', 'The foundation of system programming. Learn memory management and pointers.', '#A8B9CC'),
('C++', 'cpp', '🟠', 'Object-oriented extension of C. Great for competitive programming.', '#F34B7D'),
('Java', 'java', '🔴', 'Write once, run anywhere. Industry-standard for enterprise apps.', '#B07219'),
('Python', 'python', '🟡', 'Simple, readable, and powerful. Perfect for beginners and data science.', '#3572A5'),
('PHP', 'php', '🟣', 'Server-side scripting for dynamic web pages and APIs.', '#4F5D95'),
('JavaScript', 'javascript', '🟨', 'The language of the web. Make websites interactive and dynamic.', '#F1E05A'),
('HTML', 'html', '🟧', 'Structure your web content. The backbone of every website.', '#E34C26'),
('CSS', 'css', '🔷', 'Style and design your web pages. Make the web beautiful.', '#563D7C');

-- Levels
INSERT INTO levels (level_number, title, description, xp_reward) VALUES
(1, 'Programming Basics', 'Introduction to programming concepts, environment setup, and your first program.', 100),
(2, 'Variables and Data Types', 'Learn how to store and work with different types of data.', 150),
(3, 'Control Flow', 'Master if-else statements and switch cases to control program flow.', 200),
(4, 'Loops', 'Repeat actions efficiently using for, while, and do-while loops.', 200),
(5, 'Functions', 'Organize code into reusable blocks with functions and methods.', 250),
(6, 'Data Structures', 'Work with arrays, lists, stacks, queues and more.', 300),
(7, 'Interview & Placement Prep', 'Practice common interview questions and placement preparation topics.', 400);

-- ============================================================
-- LESSONS: PYTHON
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(4, 1, 'Python Basics', 
'Python is a high-level, interpreted programming language created by Guido van Rossum in 1991. It is known for its simple, readable syntax that resembles plain English. Python is used in web development, data science, artificial intelligence, automation, and much more. To print output in Python, we use the print() function.',
'print("Your message here")',
'# My first Python program\nprint("Hello, World!")\nprint("Welcome to Python!")',
'Print your own name to the screen.',
'Use the print() function with your name inside quotes.',
'print("Siya")'),

(4, 2, 'Python Variables and Data Types',
'Variables are containers for storing data values. In Python, you do not need to declare variable types – Python figures it out automatically. Main data types: int (whole numbers), float (decimal numbers), str (text/strings), bool (True/False).',
'variable_name = value\nname = "Alice"\nage = 20\ngpa = 8.5\nis_student = True',
'name = "Alice"\nage = 20\ngpa = 8.5\nis_student = True\n\nprint(name)\nprint(age)\nprint(gpa)\nprint(is_student)\nprint(type(name))',
'Create variables for your name, age, and city, then print all three.',
'Use = to assign values. Example: name = "Your Name"',
'name = "Siya"\nage = 21\ncity = "Mumbai"\nprint(name)\nprint(age)\nprint(city)'),

(4, 3, 'Python Control Flow',
'Control flow allows your program to make decisions. The if statement checks a condition. If the condition is True, a block of code runs. else handles the case when the condition is False. elif is used for multiple conditions.',
'if condition:\n    # code\nelif another_condition:\n    # code\nelse:\n    # code',
'marks = 75\n\nif marks >= 90:\n    print("Grade: A")\nelif marks >= 75:\n    print("Grade: B")\nelif marks >= 60:\n    print("Grade: C")\nelse:\n    print("Grade: F")',
'Write a program that checks if a number is positive, negative, or zero.',
'Use if, elif, and else. Check if number > 0, number < 0, or number == 0.',
'number = 5\n\nif number > 0:\n    print("Positive")\nelif number < 0:\n    print("Negative")\nelse:\n    print("Zero")'),

(4, 4, 'Python Loops',
'Loops allow you to execute a block of code multiple times. The for loop iterates over a sequence. The while loop repeats as long as a condition is True. range() generates a sequence of numbers.',
'for i in range(n):\n    # code\n\nwhile condition:\n    # code',
'# for loop example\nfor i in range(1, 6):\n    print(i)\n\n# while loop example\ncount = 1\nwhile count <= 5:\n    print(count)\n    count += 1',
'Print the multiplication table of 5 (5x1 to 5x10) using a loop.',
'Use for i in range(1, 11): and print(5 * i)',
'for i in range(1, 11):\n    print(f"5 x {i} = {5 * i}")'),

(4, 5, 'Python Functions',
'A function is a reusable block of code that performs a specific task. Functions help organize code, avoid repetition, and make programs easier to read. You define a function using def keyword.',
'def function_name(parameters):\n    # code\n    return value',
'def greet(name):\n    return f"Hello, {name}!"\n\ndef add(a, b):\n    return a + b\n\nprint(greet("Alice"))\nresult = add(10, 20)\nprint(result)',
'Write a function that takes two numbers and returns the larger one.',
'Use if a > b: return a else: return b inside your function.',
'def find_max(a, b):\n    if a > b:\n        return a\n    else:\n        return b\n\nprint(find_max(10, 25))'),

(4, 6, 'Python Data Structures',
'Python has built-in data structures: List (ordered, mutable), Tuple (ordered, immutable), Dictionary (key-value pairs), Set (unordered, no duplicates). Lists are most commonly used – they store multiple items in one variable.',
'# List\nmy_list = [1, 2, 3]\n\n# Dictionary\nmy_dict = {"key": "value"}\n\n# Tuple\nmy_tuple = (1, 2, 3)',
'# List operations\nfruits = ["apple", "banana", "mango"]\nfruits.append("orange")\nprint(fruits)\nprint(fruits[0])\n\n# Dictionary\nstudent = {"name": "Alice", "age": 20}\nprint(student["name"])',
'Create a list of 5 subjects and print each subject using a loop.',
'Create a list with [] and use for subject in subjects: to loop through it.',
'subjects = ["Math", "Science", "English", "Python", "History"]\nfor subject in subjects:\n    print(subject)'),

(4, 7, 'Python Interview Prep',
'Common Python interview topics include: list comprehensions, lambda functions, decorators, file handling, exception handling, OOP concepts, and common algorithms. Practice these patterns to ace your placement interviews.',
'# List comprehension\nsquares = [x**2 for x in range(1, 6)]\n\n# Lambda\nfn = lambda x: x * 2\n\n# Try-Except\ntry:\n    x = 1/0\nexcept ZeroDivisionError:\n    print("Error!")',
'# Reverse a string\ndef reverse_string(s):\n    return s[::-1]\n\n# Check palindrome\ndef is_palindrome(s):\n    return s == s[::-1]\n\n# Fibonacci\ndef fibonacci(n):\n    a, b = 0, 1\n    for _ in range(n):\n        print(a, end=" ")\n        a, b = b, a + b\n\nfibonacci(7)',
'Write a function to check if a number is prime.',
'A prime number is divisible only by 1 and itself. Check from 2 to n-1.',
'def is_prime(n):\n    if n < 2:\n        return False\n    for i in range(2, n):\n        if n % i == 0:\n            return False\n    return True\n\nprint(is_prime(7))\nprint(is_prime(10))');

-- ============================================================
-- LESSONS: JavaScript
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(6, 1, 'JavaScript Basics',
'JavaScript is a scripting language used to make web pages interactive. It runs in the browser (and also on servers with Node.js). You can use console.log() to print output. JavaScript is case-sensitive and uses semicolons at the end of statements.',
'console.log("Your message here");',
'// My first JavaScript program\nconsole.log("Hello, World!");\nconsole.log("Welcome to JavaScript!");\n\n// You can also use alert in browser\n// alert("Hello!");',
'Print your name and the current year using console.log.',
'Use console.log() to print text. Put text inside quotes.',
'console.log("Siya");\nconsole.log(2024);'),

(6, 2, 'JavaScript Variables and Data Types',
'Variables store data values. Use let for variables that can change, const for constants, and var (older way). Data types include: String, Number, Boolean, null, undefined, Object, and Array.',
'let name = "Alice";\nconst PI = 3.14;\nvar age = 20;',
'let name = "Alice";\nlet age = 20;\nlet gpa = 8.5;\nlet isStudent = true;\n\nconsole.log(name);\nconsole.log(age);\nconsole.log(typeof name);\nconsole.log(typeof age);',
'Create variables for your name, age, and favorite color, then print them.',
'Use let to create variables. Use console.log() to print.',
'let name = "Siya";\nlet age = 21;\nlet favoriteColor = "Blue";\nconsole.log(name);\nconsole.log(age);\nconsole.log(favoriteColor);'),

(6, 3, 'JavaScript Control Flow',
'If-else statements let your program make decisions. The condition inside if() is evaluated – if true, that block runs. Use === for strict equality comparison in JavaScript.',
'if (condition) {\n    // code\n} else if (another) {\n    // code\n} else {\n    // code\n}',
'let score = 82;\n\nif (score >= 90) {\n    console.log("Grade: A");\n} else if (score >= 75) {\n    console.log("Grade: B");\n} else if (score >= 60) {\n    console.log("Grade: C");\n} else {\n    console.log("Grade: F");\n}',
'Write code to check if a person is eligible to vote (age >= 18).',
'Check if age >= 18. If yes print "Eligible to vote" else "Not eligible".',
'let age = 20;\n\nif (age >= 18) {\n    console.log("Eligible to vote");\n} else {\n    console.log("Not eligible to vote");\n}'),

(6, 4, 'JavaScript Loops',
'Loops execute code repeatedly. for loops run for a known number of times. while loops run while a condition is true. forEach is used to iterate over arrays.',
'for (let i = 0; i < n; i++) {\n    // code\n}\n\nwhile (condition) {\n    // code\n}',
'// for loop\nfor (let i = 1; i <= 5; i++) {\n    console.log("Count: " + i);\n}\n\n// while loop\nlet num = 1;\nwhile (num <= 5) {\n    console.log(num);\n    num++;\n}',
'Print the sum of numbers from 1 to 10 using a loop.',
'Use a for loop and add each number to a sum variable. Start sum = 0.',
'let sum = 0;\nfor (let i = 1; i <= 10; i++) {\n    sum += i;\n}\nconsole.log("Sum:", sum);'),

(6, 5, 'JavaScript Functions',
'Functions are blocks of reusable code. You can define them using function keyword or as arrow functions (=>). Functions can take parameters and return values.',
'function name(params) {\n    return value;\n}\n\n// Arrow function\nconst name = (params) => value;',
'function greet(name) {\n    return "Hello, " + name + "!";\n}\n\nconst multiply = (a, b) => a * b;\n\nconsole.log(greet("Alice"));\nconsole.log(multiply(4, 5));',
'Write a function that calculates the area of a rectangle (length × width).',
'Define a function with two parameters length and width. Return length * width.',
'function rectangleArea(length, width) {\n    return length * width;\n}\n\nconsole.log(rectangleArea(5, 3));'),

(6, 6, 'JavaScript Data Structures',
'Arrays store multiple values in one variable. Objects store key-value pairs. Arrays have useful methods like push(), pop(), map(), filter(), and find().',
'let arr = [1, 2, 3];\nlet obj = {key: "value"};',
'let fruits = ["apple", "banana", "mango"];\nfruits.push("orange");\nconsole.log(fruits);\nconsole.log(fruits[0]);\n\nlet student = {\n    name: "Alice",\n    age: 20,\n    grade: "A"\n};\nconsole.log(student.name);',
'Create an array of 5 numbers and find the largest using Math.max.',
'Use Math.max(...yourArray) to get the max value, or loop through.',
'let numbers = [4, 7, 2, 9, 1];\nlet max = Math.max(...numbers);\nconsole.log("Largest:", max);'),

(6, 7, 'JavaScript Interview Prep',
'Important JS interview topics: closures, hoisting, promises, async/await, event loop, prototypes, ES6+ features, and common algorithms. Practice these concepts.',
'// Closure\nfunction counter() {\n    let count = 0;\n    return () => ++count;\n}\n\n// Promise\nconst p = new Promise((resolve) => resolve("Done"));',
'// Check palindrome\nfunction isPalindrome(str) {\n    return str === str.split("").reverse().join("");\n}\n\n// Find duplicates\nfunction findDuplicates(arr) {\n    return arr.filter((item, index) => arr.indexOf(item) !== index);\n}\n\nconsole.log(isPalindrome("racecar"));\nconsole.log(findDuplicates([1,2,3,2,4,1]));',
'Write a function to flatten a nested array like [1,[2,[3,4]]] into [1,2,3,4].',
'Use Array.flat(Infinity) or recursion to flatten nested arrays.',
'function flattenArray(arr) {\n    return arr.flat(Infinity);\n}\n\nconsole.log(flattenArray([1, [2, [3, 4]], 5]));');

-- ============================================================
-- LESSONS: C
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(1, 1, 'C Programming Basics',
'C is a general-purpose programming language developed by Dennis Ritchie in 1972. It is considered the mother of all modern languages. Every C program starts execution from the main() function. printf() is used to print output. Every statement ends with a semicolon.',
'#include <stdio.h>\nint main() {\n    printf("Hello");\n    return 0;\n}',
'#include <stdio.h>\n\nint main() {\n    printf("Hello, World!\\n");\n    printf("Welcome to C Programming!\\n");\n    return 0;\n}',
'Print your name and college using printf().',
'Use printf("Your Name"); Remember to add \\n for a new line.',
'#include <stdio.h>\n\nint main() {\n    printf("Siya\\n");\n    printf("ABC College\\n");\n    return 0;\n}'),

(1, 2, 'C Variables and Data Types',
'Variables store data in memory. In C, you must declare the type before using a variable. Common types: int (integer), float (decimal), char (single character), double (large decimal). Use scanf() to take user input.',
'int x = 5;\nfloat f = 3.14;\nchar c = ''A'';',
'#include <stdio.h>\n\nint main() {\n    int age = 20;\n    float gpa = 8.5;\n    char grade = ''A'';\n    \n    printf("Age: %d\\n", age);\n    printf("GPA: %.1f\\n", gpa);\n    printf("Grade: %c\\n", grade);\n    return 0;\n}',
'Declare variables for your age, height (float), and first initial (char), then print them.',
'Use int, float, char types. Use %d, %f, %c in printf() for formatting.',
'#include <stdio.h>\n\nint main() {\n    int age = 21;\n    float height = 5.6;\n    char initial = ''S'';\n    printf("Age: %d\\n", age);\n    printf("Height: %.1f\\n", height);\n    printf("Initial: %c\\n", initial);\n    return 0;\n}'),

(1, 3, 'C Control Flow',
'If-else allows conditional execution. The switch statement is used when checking a variable against multiple values. Always include break in switch cases to prevent fall-through.',
'if (condition) {\n    // code\n} else {\n    // code\n}\n\nswitch(var) {\n    case 1: break;\n    default: break;\n}',
'#include <stdio.h>\n\nint main() {\n    int marks = 75;\n    \n    if (marks >= 90) {\n        printf("A Grade\\n");\n    } else if (marks >= 75) {\n        printf("B Grade\\n");\n    } else {\n        printf("C Grade\\n");\n    }\n    return 0;\n}',
'Check if a number is even or odd using the modulo operator %.',
'If num % 2 == 0, it is even. Otherwise it is odd.',
'#include <stdio.h>\n\nint main() {\n    int num = 7;\n    if (num % 2 == 0) {\n        printf("Even\\n");\n    } else {\n        printf("Odd\\n");\n    }\n    return 0;\n}'),

(1, 4, 'C Loops',
'Loops repeat code. The for loop is used when the number of iterations is known. The while loop repeats while a condition is true. The do-while loop runs at least once.',
'for(int i=0; i<n; i++) {}\nwhile(condition) {}\ndo { } while(condition);',
'#include <stdio.h>\n\nint main() {\n    // for loop\n    for(int i = 1; i <= 5; i++) {\n        printf("%d\\n", i);\n    }\n    \n    // while loop\n    int j = 1;\n    while(j <= 5) {\n        printf("%d\\n", j);\n        j++;\n    }\n    return 0;\n}',
'Print the first 10 natural numbers using a for loop.',
'Use for(int i=1; i<=10; i++) and printf inside the loop.',
'#include <stdio.h>\n\nint main() {\n    for(int i = 1; i <= 10; i++) {\n        printf("%d\\n", i);\n    }\n    return 0;\n}'),

(1, 5, 'C Functions',
'A function is a block of code that performs a task. Functions avoid code repetition. You declare return type, name, and parameters. Use return to send back a value.',
'return_type function_name(parameters) {\n    // code\n    return value;\n}',
'#include <stdio.h>\n\nint add(int a, int b) {\n    return a + b;\n}\n\nvoid greet(char name[]) {\n    printf("Hello, %s!\\n", name);\n}\n\nint main() {\n    int sum = add(10, 20);\n    printf("Sum: %d\\n", sum);\n    greet("Alice");\n    return 0;\n}',
'Write a function to find the factorial of a number.',
'Factorial of n = n * (n-1) * ... * 1. Use a loop inside the function.',
'#include <stdio.h>\n\nint factorial(int n) {\n    int result = 1;\n    for(int i = 1; i <= n; i++) {\n        result *= i;\n    }\n    return result;\n}\n\nint main() {\n    printf("Factorial of 5: %d\\n", factorial(5));\n    return 0;\n}'),

(1, 6, 'C Data Structures - Arrays',
'An array stores multiple values of the same type in contiguous memory. Arrays in C are zero-indexed (start from 0). You must declare the size at the time of creation.',
'int arr[5] = {1, 2, 3, 4, 5};\narr[0]; // first element',
'#include <stdio.h>\n\nint main() {\n    int arr[5] = {10, 20, 30, 40, 50};\n    \n    for(int i = 0; i < 5; i++) {\n        printf("arr[%d] = %d\\n", i, arr[i]);\n    }\n    \n    // Find sum\n    int sum = 0;\n    for(int i = 0; i < 5; i++) sum += arr[i];\n    printf("Sum: %d\\n", sum);\n    return 0;\n}',
'Create an array of 5 marks and find the average.',
'Sum all elements and divide by 5 to get the average.',
'#include <stdio.h>\n\nint main() {\n    int marks[5] = {80, 90, 75, 85, 95};\n    int sum = 0;\n    for(int i = 0; i < 5; i++) sum += marks[i];\n    printf("Average: %.1f\\n", (float)sum/5);\n    return 0;\n}'),

(1, 7, 'C Interview Prep',
'Important C interview topics: pointers, memory management, string operations, recursion, sorting algorithms, and common coding problems. Understand memory addresses and pointer arithmetic.',
'int x = 10;\nint *ptr = &x;\nprintf("%d", *ptr); // outputs 10',
'#include <stdio.h>\n\n// Bubble Sort\nvoid bubbleSort(int arr[], int n) {\n    for(int i = 0; i < n-1; i++) {\n        for(int j = 0; j < n-i-1; j++) {\n            if(arr[j] > arr[j+1]) {\n                int temp = arr[j];\n                arr[j] = arr[j+1];\n                arr[j+1] = temp;\n            }\n        }\n    }\n}\n\nint main() {\n    int arr[] = {64, 34, 25, 12};\n    bubbleSort(arr, 4);\n    for(int i = 0; i < 4; i++) printf("%d ", arr[i]);\n    return 0;\n}',
'Write a recursive function to calculate the nth Fibonacci number.',
'Fibonacci: F(n) = F(n-1) + F(n-2), where F(0)=0, F(1)=1.',
'#include <stdio.h>\n\nint fibonacci(int n) {\n    if(n <= 1) return n;\n    return fibonacci(n-1) + fibonacci(n-2);\n}\n\nint main() {\n    for(int i = 0; i < 8; i++) printf("%d ", fibonacci(i));\n    return 0;\n}');

-- ============================================================
-- LESSONS: C++
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(2, 1, 'C++ Basics',
'C++ is an extension of C with Object-Oriented Programming features. It was created by Bjarne Stroustrup. C++ uses cout for output and cin for input. The #include <iostream> header is required for input/output operations.',
'#include <iostream>\nusing namespace std;\ncout << "Hello" << endl;',
'#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Hello, World!" << endl;\n    cout << "Welcome to C++!" << endl;\n    return 0;\n}',
'Print your name and your favorite programming language using cout.',
'Use cout << "Your Text" << endl; Remember endl starts a new line.',
'#include <iostream>\nusing namespace std;\n\nint main() {\n    cout << "Siya" << endl;\n    cout << "Python" << endl;\n    return 0;\n}'),

(2, 2, 'C++ Variables and Data Types',
'C++ supports all C data types plus more. string is a built-in type for text (need #include <string>). bool is for true/false values. auto keyword lets compiler deduce the type automatically.',
'int x = 5;\nstring name = "Alice";\nbool flag = true;\nauto val = 3.14;',
'#include <iostream>\n#include <string>\nusing namespace std;\n\nint main() {\n    string name = "Alice";\n    int age = 20;\n    float gpa = 9.1;\n    bool isStudent = true;\n    \n    cout << "Name: " << name << endl;\n    cout << "Age: " << age << endl;\n    cout << "GPA: " << gpa << endl;\n    cout << "Student: " << isStudent << endl;\n    return 0;\n}',
'Create variables for a product: name (string), price (float), quantity (int), and print them.',
'Declare string, float, int variables and use cout to print each one.',
'#include <iostream>\n#include <string>\nusing namespace std;\n\nint main() {\n    string productName = "Laptop";\n    float price = 45000.50;\n    int quantity = 5;\n    cout << productName << " " << price << " " << quantity << endl;\n    return 0;\n}'),

(2, 3, 'C++ Control Flow',
'C++ uses the same if-else and switch as C. The ternary operator ?: is a shortcut for simple if-else. Comparison operators: ==, !=, <, >, <=, >= . Logical: &&, ||, !',
'if (x > 0) {\n    // positive\n} else {\n    // negative\n}\nint max = (a > b) ? a : b;',
'#include <iostream>\nusing namespace std;\n\nint main() {\n    int score = 78;\n    \n    if (score >= 90) cout << "A" << endl;\n    else if (score >= 75) cout << "B" << endl;\n    else if (score >= 60) cout << "C" << endl;\n    else cout << "Fail" << endl;\n    \n    // Ternary\n    int a = 10, b = 20;\n    int max = (a > b) ? a : b;\n    cout << "Max: " << max << endl;\n    return 0;\n}',
'Write a program to check if a year is a leap year.',
'A year is leap if divisible by 4, but century years must also be divisible by 400.',
'#include <iostream>\nusing namespace std;\n\nint main() {\n    int year = 2024;\n    if ((year % 4 == 0 && year % 100 != 0) || year % 400 == 0)\n        cout << "Leap Year" << endl;\n    else\n        cout << "Not a Leap Year" << endl;\n    return 0;\n}'),

(2, 4, 'C++ Loops',
'C++ has for, while, do-while loops. Range-based for loop (for each) is great for containers. break exits the loop. continue skips to the next iteration.',
'for(int i=0; i<n; i++){}\nfor(auto x : collection){}\nwhile(condition){}',
'#include <iostream>\nusing namespace std;\n\nint main() {\n    // Print pattern\n    for(int i = 1; i <= 5; i++) {\n        for(int j = 1; j <= i; j++) {\n            cout << "* ";\n        }\n        cout << endl;\n    }\n    return 0;\n}',
'Print a right triangle pattern of stars with 5 rows.',
'Use nested for loops. Outer loop for rows, inner loop for stars.',
'#include <iostream>\nusing namespace std;\n\nint main() {\n    for(int i = 1; i <= 5; i++) {\n        for(int j = 1; j <= i; j++) cout << "* ";\n        cout << endl;\n    }\n    return 0;\n}'),

(2, 5, 'C++ Functions and OOP Basics',
'C++ supports functions like C, plus adds Object-Oriented Programming. A class is a blueprint for objects. Objects have attributes (data) and methods (functions). Use public/private for access control.',
'class ClassName {\npublic:\n    int attribute;\n    void method() {}\n};',
'#include <iostream>\n#include <string>\nusing namespace std;\n\nclass Student {\npublic:\n    string name;\n    int age;\n    \n    void display() {\n        cout << "Name: " << name << endl;\n        cout << "Age: " << age << endl;\n    }\n};\n\nint main() {\n    Student s;\n    s.name = "Alice";\n    s.age = 20;\n    s.display();\n    return 0;\n}',
'Create a Car class with attributes color and speed. Add a method to display them.',
'Define class Car with public attributes and a void display() method.',
'#include <iostream>\n#include <string>\nusing namespace std;\n\nclass Car {\npublic:\n    string color;\n    int speed;\n    void display() {\n        cout << "Color: " << color << endl;\n        cout << "Speed: " << speed << " km/h" << endl;\n    }\n};\n\nint main() {\n    Car c;\n    c.color = "Red";\n    c.speed = 120;\n    c.display();\n    return 0;\n}'),

(2, 6, 'C++ STL Data Structures',
'The Standard Template Library (STL) provides ready-made data structures: vector (dynamic array), map (key-value), set (unique values), stack, queue, and more. These save time and are interview favorites.',
'#include <vector>\nvector<int> v = {1,2,3};\nv.push_back(4);\n\n#include <map>\nmap<string,int> m;\nm["key"] = 10;',
'#include <iostream>\n#include <vector>\n#include <map>\n#include <algorithm>\nusing namespace std;\n\nint main() {\n    vector<int> nums = {5,2,8,1,9};\n    sort(nums.begin(), nums.end());\n    for(int n : nums) cout << n << " ";\n    cout << endl;\n    \n    map<string, int> scores;\n    scores["Alice"] = 95;\n    scores["Bob"] = 87;\n    for(auto p : scores) cout << p.first << ": " << p.second << endl;\n    return 0;\n}',
'Use a vector to store 5 student marks, then find the highest mark.',
'Use *max_element(v.begin(), v.end()) to find the maximum.',
'#include <iostream>\n#include <vector>\n#include <algorithm>\nusing namespace std;\n\nint main() {\n    vector<int> marks = {85, 92, 78, 95, 88};\n    int highest = *max_element(marks.begin(), marks.end());\n    cout << "Highest mark: " << highest << endl;\n    return 0;\n}'),

(2, 7, 'C++ Interview Prep',
'Key C++ interview topics: OOP principles (inheritance, polymorphism, encapsulation, abstraction), virtual functions, templates, STL algorithms, pointers, and memory management with new/delete.',
'class Animal {\npublic:\n    virtual void sound() = 0; // pure virtual\n};\nclass Dog : public Animal {\npublic:\n    void sound() { cout << "Woof"; }\n};',
'#include <iostream>\nusing namespace std;\n\n// Binary Search\nint binarySearch(int arr[], int n, int target) {\n    int left = 0, right = n - 1;\n    while(left <= right) {\n        int mid = left + (right - left) / 2;\n        if(arr[mid] == target) return mid;\n        if(arr[mid] < target) left = mid + 1;\n        else right = mid - 1;\n    }\n    return -1;\n}\n\nint main() {\n    int arr[] = {1, 3, 5, 7, 9, 11};\n    int idx = binarySearch(arr, 6, 7);\n    cout << "Found at index: " << idx << endl;\n    return 0;\n}',
'Implement a Stack using a vector with push, pop, and top operations.',
'Use vector as underlying storage. push_back() to push, pop_back() to pop, back() for top.',
'#include <iostream>\n#include <vector>\nusing namespace std;\n\nclass Stack {\n    vector<int> data;\npublic:\n    void push(int x) { data.push_back(x); }\n    void pop() { if(!data.empty()) data.pop_back(); }\n    int top() { return data.back(); }\n    bool empty() { return data.empty(); }\n};\n\nint main() {\n    Stack s;\n    s.push(1); s.push(2); s.push(3);\n    cout << s.top() << endl;\n    s.pop();\n    cout << s.top() << endl;\n    return 0;\n}');

-- ============================================================
-- LESSONS: Java
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(3, 1, 'Java Basics',
'Java is a class-based, object-oriented language designed by James Gosling. It follows the principle "Write Once, Run Anywhere". Every Java program must have a class, and execution starts at the main method. System.out.println() is used to print output.',
'public class MyClass {\n    public static void main(String[] args) {\n        System.out.println("Hello");\n    }\n}',
'public class HelloWorld {\n    public static void main(String[] args) {\n        System.out.println("Hello, World!");\n        System.out.println("Welcome to Java!");\n    }\n}',
'Print "Java is fun!" and your name on separate lines.',
'Use System.out.println() for each line of output.',
'public class MyProgram {\n    public static void main(String[] args) {\n        System.out.println("Java is fun!");\n        System.out.println("Siya");\n    }\n}'),

(3, 2, 'Java Variables and Data Types',
'Java is a statically typed language. You must declare the type of each variable. Primitive types: int, long, float, double, char, boolean, byte, short. String is a class (non-primitive). Java automatically converts smaller types to larger ones (widening).',
'int age = 20;\nString name = "Alice";\ndouble price = 9.99;\nboolean isActive = true;',
'public class DataTypes {\n    public static void main(String[] args) {\n        String name = "Alice";\n        int age = 20;\n        double gpa = 9.1;\n        boolean isStudent = true;\n        char grade = ''A'';\n        \n        System.out.println("Name: " + name);\n        System.out.println("Age: " + age);\n        System.out.println("GPA: " + gpa);\n    }\n}',
'Create a program with variables for a book: title, author, pages (int), and price (double).',
'Declare String for title and author, int for pages, double for price.',
'public class Book {\n    public static void main(String[] args) {\n        String title = "Java Basics";\n        String author = "James";\n        int pages = 350;\n        double price = 499.99;\n        System.out.println(title + " by " + author);\n        System.out.println("Pages: " + pages + " Price: " + price);\n    }\n}'),

(3, 3, 'Java Control Flow',
'Java uses if-else and switch-case for control flow. The switch statement in Java supports int, char, String, and enum. Always add break in switch to avoid fall-through. Java also has the ternary operator.',
'if (x > 0) { }\nelse if (x < 0) { }\nelse { }\n\nswitch (day) {\n    case "Mon": break;\n    default: break;\n}',
'public class ControlFlow {\n    public static void main(String[] args) {\n        int day = 3;\n        switch(day) {\n            case 1: System.out.println("Monday"); break;\n            case 2: System.out.println("Tuesday"); break;\n            case 3: System.out.println("Wednesday"); break;\n            default: System.out.println("Other");\n        }\n    }\n}',
'Write a program to check if a number entered is positive, negative, or zero.',
'Compare the number with 0 using if-else if-else.',
'public class CheckNumber {\n    public static void main(String[] args) {\n        int num = -5;\n        if (num > 0) System.out.println("Positive");\n        else if (num < 0) System.out.println("Negative");\n        else System.out.println("Zero");\n    }\n}'),

(3, 4, 'Java Loops',
'Java has three types of loops: for, while, do-while. Enhanced for-each loop is used to iterate arrays/collections. break exits a loop. continue skips the current iteration.',
'for(int i=0; i<n; i++){}\nfor(int x : array){}\nwhile(cond){}\ndo{}while(cond);',
'public class Loops {\n    public static void main(String[] args) {\n        // Multiplication table\n        int n = 5;\n        for(int i = 1; i <= 10; i++) {\n            System.out.println(n + " x " + i + " = " + (n * i));\n        }\n    }\n}',
'Print all even numbers from 1 to 20 using a for loop.',
'Check if i % 2 == 0 inside the loop and print only those numbers.',
'public class EvenNumbers {\n    public static void main(String[] args) {\n        for(int i = 1; i <= 20; i++) {\n            if(i % 2 == 0) System.out.println(i);\n        }\n    }\n}'),

(3, 5, 'Java Methods and OOP',
'Methods are blocks of code that perform tasks. Java is fully object-oriented. Classes have fields (attributes) and methods (behavior). Constructors initialize objects. Access modifiers: public, private, protected.',
'class Animal {\n    private String name;\n    public Animal(String n) { name = n; }\n    public void speak() { System.out.println("..."); }\n}',
'public class Student {\n    String name;\n    int rollNo;\n    double marks;\n    \n    Student(String n, int r, double m) {\n        name = n; rollNo = r; marks = m;\n    }\n    \n    void display() {\n        System.out.println("Name: " + name + " Roll: " + rollNo + " Marks: " + marks);\n    }\n    \n    public static void main(String[] args) {\n        Student s = new Student("Alice", 101, 89.5);\n        s.display();\n    }\n}',
'Create a Rectangle class with length and width. Add a method to calculate and return the area.',
'Use a constructor to set length and width. Return length * width from getArea() method.',
'public class Rectangle {\n    int length, width;\n    Rectangle(int l, int w) { length=l; width=w; }\n    int getArea() { return length * width; }\n    public static void main(String[] args) {\n        Rectangle r = new Rectangle(5, 3);\n        System.out.println("Area: " + r.getArea());\n    }\n}'),

(3, 6, 'Java Collections Framework',
'Java Collections provide data structures: ArrayList (dynamic array), LinkedList, HashMap (key-value), HashSet (unique), Stack, Queue. Import from java.util package. Very important for interviews.',
'import java.util.*;\nArrayList<String> list = new ArrayList<>();\nHashMap<String,Integer> map = new HashMap<>();',
'import java.util.*;\n\npublic class Collections {\n    public static void main(String[] args) {\n        ArrayList<String> names = new ArrayList<>();\n        names.add("Alice");\n        names.add("Bob");\n        names.add("Charlie");\n        \n        for(String name : names) System.out.println(name);\n        \n        HashMap<String, Integer> scores = new HashMap<>();\n        scores.put("Alice", 95);\n        scores.put("Bob", 87);\n        System.out.println(scores);\n    }\n}',
'Use ArrayList to store marks of 5 students and calculate the average.',
'Add marks with add(), then loop and sum. Divide by size() for average.',
'import java.util.*;\n\npublic class Average {\n    public static void main(String[] args) {\n        ArrayList<Integer> marks = new ArrayList<>(Arrays.asList(80,90,75,85,95));\n        int sum = 0;\n        for(int m : marks) sum += m;\n        System.out.println("Average: " + (double)sum/marks.size());\n    }\n}'),

(3, 7, 'Java Interview Prep',
'Top Java interview topics: OOP principles, exception handling, multithreading, Java 8 features (lambda, streams), design patterns, and common algorithms. Practice sorting, searching, and string problems.',
'// Lambda & Streams\nList<Integer> evens = nums.stream()\n    .filter(n -> n % 2 == 0)\n    .collect(Collectors.toList());',
'import java.util.*;\n\npublic class InterviewPrep {\n    // Reverse a string\n    static String reverse(String s) {\n        return new StringBuilder(s).reverse().toString();\n    }\n    \n    // Check anagram\n    static boolean isAnagram(String a, String b) {\n        char[] ca = a.toCharArray(); char[] cb = b.toCharArray();\n        Arrays.sort(ca); Arrays.sort(cb);\n        return Arrays.equals(ca, cb);\n    }\n    \n    public static void main(String[] args) {\n        System.out.println(reverse("hello"));\n        System.out.println(isAnagram("listen", "silent"));\n    }\n}',
'Write a program to find all prime numbers between 1 and 50.',
'For each number, check if it has any divisor other than 1 and itself.',
'public class Primes {\n    static boolean isPrime(int n) {\n        if(n<2) return false;\n        for(int i=2;i<=Math.sqrt(n);i++) if(n%i==0) return false;\n        return true;\n    }\n    public static void main(String[] args) {\n        for(int i=1;i<=50;i++) if(isPrime(i)) System.out.print(i+" ");\n    }\n}');

-- ============================================================
-- LESSONS: PHP
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(5, 1, 'PHP Basics',
'PHP (Hypertext Preprocessor) is a server-side scripting language used for web development. PHP code is embedded in HTML using <?php ?> tags. echo or print is used to output text. PHP files have a .php extension.',
'<?php\n  echo "Hello, World!";\n?>',
'<?php\n  echo "Hello, World!<br>";\n  echo "Welcome to PHP!<br>";\n  print("This is PHP programming");\n?>',
'Print your name and current year using echo.',
'Use echo "Your Text"; or echo "Text" . date("Y"); for current year.',
'<?php\n  echo "Siya<br>";\n  echo date("Y");\n?>'),

(5, 2, 'PHP Variables and Data Types',
'PHP variables start with a dollar sign $. PHP is loosely typed – no need to declare type. Types include string, integer, float, boolean, array, NULL. var_dump() shows type and value. gettype() returns the type name.',
'$name = "Alice";\n$age = 20;\n$price = 9.99;\n$active = true;',
'<?php\n  $name = "Alice";\n  $age = 20;\n  $gpa = 8.5;\n  $isStudent = true;\n  \n  echo "Name: " . $name . "<br>";\n  echo "Age: " . $age . "<br>";\n  echo "GPA: " . $gpa . "<br>";\n  var_dump($isStudent);\n?>',
'Create PHP variables for a product (name, price, stock) and display them.',
'Use $ prefix for each variable. Use echo to print. Concatenate with dot (.).',
'<?php\n  $productName = "Laptop";\n  $price = 45000;\n  $stock = 10;\n  echo "Product: " . $productName . "<br>";\n  echo "Price: " . $price . "<br>";\n  echo "Stock: " . $stock;\n?>'),

(5, 3, 'PHP Control Flow',
'PHP supports if, elseif, else, and switch statements. The match expression is available in PHP 8. PHP comparison: == (loose), === (strict). Logical operators: &&, ||, !',
'if ($x > 0) {\n  // code\n} elseif ($x < 0) {\n  // code\n} else {\n  // code\n}',
'<?php\n  $marks = 82;\n  \n  if ($marks >= 90) {\n    echo "Grade A";\n  } elseif ($marks >= 75) {\n    echo "Grade B";\n  } elseif ($marks >= 60) {\n    echo "Grade C";\n  } else {\n    echo "Fail";\n  }\n?>',
'Check if a number is divisible by both 3 and 5 (FizzBuzz for one number).',
'Use % operator. Check if $num % 3 == 0 AND $num % 5 == 0.',
'<?php\n  $num = 15;\n  if ($num % 3 == 0 && $num % 5 == 0) echo "FizzBuzz";\n  elseif ($num % 3 == 0) echo "Fizz";\n  elseif ($num % 5 == 0) echo "Buzz";\n  else echo $num;\n?>'),

(5, 4, 'PHP Loops',
'PHP has four loop types: for, while, do-while, and foreach. foreach is specifically designed for arrays. You can use break and continue in loops.',
'for($i=0; $i<n; $i++){}\nwhile($cond){}\nforeach($array as $value){}',
'<?php\n  // for loop\n  for($i = 1; $i <= 5; $i++) {\n    echo "Line $i<br>";\n  }\n  \n  // foreach with array\n  $fruits = ["Apple", "Banana", "Mango"];\n  foreach($fruits as $fruit) {\n    echo $fruit . "<br>";\n  }\n?>',
'Print the multiplication table of 7 using a for loop.',
'Use for($i=1; $i<=10; $i++) and echo 7 * $i inside the loop.',
'<?php\n  for($i = 1; $i <= 10; $i++) {\n    echo "7 x $i = " . (7 * $i) . "<br>";\n  }\n?>'),

(5, 5, 'PHP Functions',
'PHP functions are defined with the function keyword. Functions can have default parameter values. PHP supports variable-length argument lists. Return type declarations are supported in PHP 7+.',
'function functionName($param1, $param2 = "default") {\n  return $value;\n}',
'<?php\n  function greet($name) {\n    return "Hello, $name!";\n  }\n  \n  function calculateArea($length, $width) {\n    return $length * $width;\n  }\n  \n  echo greet("Alice") . "<br>";\n  echo "Area: " . calculateArea(5, 3);\n?>',
'Write a PHP function that takes an array of numbers and returns their sum.',
'Use array_sum($array) or loop through with foreach and add to $sum.',
'<?php\n  function sumArray($numbers) {\n    $sum = 0;\n    foreach($numbers as $num) $sum += $num;\n    return $sum;\n  }\n  \n  $nums = [10, 20, 30, 40, 50];\n  echo "Sum: " . sumArray($nums);\n?>'),

(5, 6, 'PHP Arrays',
'PHP has three types of arrays: Indexed (numbered), Associative (key-value), and Multidimensional. PHP has many built-in array functions: array_push(), array_pop(), sort(), count(), array_merge(), etc.',
'$indexed = [1, 2, 3];\n$assoc = ["name" => "Alice"];\n$multi = [[1,2],[3,4]];',
'<?php\n  // Associative array\n  $student = [\n    "name" => "Alice",\n    "age" => 20,\n    "grade" => "A"\n  ];\n  \n  echo $student["name"] . "<br>";\n  \n  // Loop through\n  foreach($student as $key => $value) {\n    echo "$key: $value<br>";\n  }\n  \n  // Sort array\n  $marks = [85, 72, 90, 68, 95];\n  sort($marks);\n  print_r($marks);\n?>',
'Create an associative array for a student with name, roll_no, and marks. Print all using foreach.',
'Use => to create key-value pairs. Loop with foreach($arr as $key => $value).',
'<?php\n  $student = ["name" => "Siya", "roll_no" => 101, "marks" => 92];\n  foreach($student as $key => $value) {\n    echo ucfirst($key) . ": $value<br>";\n  }\n?>'),

(5, 7, 'PHP Interview Prep',
'Key PHP interview topics: sessions and cookies, file handling, form validation, MySQL integration, OOP in PHP, error handling, regular expressions, and security (XSS, SQL injection prevention).',
'// Sessions\nsession_start();\n$_SESSION["user"] = "Alice";\n\n// Regex\npreg_match("/^[a-z]+$/", $str);',
'<?php\n// OOP Example\nclass BankAccount {\n    private $balance;\n    \n    public function __construct($initial) {\n        $this->balance = $initial;\n    }\n    \n    public function deposit($amount) {\n        $this->balance += $amount;\n    }\n    \n    public function getBalance() {\n        return $this->balance;\n    }\n}\n\n$acc = new BankAccount(1000);\n$acc->deposit(500);\necho "Balance: " . $acc->getBalance();\n?>',
'Write a PHP function to validate an email address using filter_var.',
'Use filter_var($email, FILTER_VALIDATE_EMAIL) – returns false if invalid.',
'<?php\n  function validateEmail($email) {\n    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {\n      return "Valid email";\n    } else {\n      return "Invalid email";\n    }\n  }\n  echo validateEmail("test@example.com") . "<br>";\n  echo validateEmail("notanemail");\n?>');

-- ============================================================
-- LESSONS: HTML
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(7, 1, 'HTML Basics',
'HTML (HyperText Markup Language) is the standard language for creating web pages. HTML uses tags enclosed in angle brackets. Tags usually come in pairs: opening <tag> and closing </tag>. The basic structure includes html, head, and body elements.',
'<tagname>Content</tagname>\n<!DOCTYPE html>\n<html>\n  <head><title>Title</title></head>\n  <body>Content</body>\n</html>',
'<!DOCTYPE html>\n<html>\n  <head>\n    <title>My First Page</title>\n  </head>\n  <body>\n    <h1>Hello, World!</h1>\n    <p>Welcome to HTML!</p>\n  </body>\n</html>',
'Create an HTML page with a heading "Welcome" and a paragraph about yourself.',
'Use <h1> for the heading and <p> for the paragraph inside <body>.',
'<!DOCTYPE html>\n<html>\n<head><title>About Me</title></head>\n<body>\n  <h1>Welcome</h1>\n  <p>My name is Siya. I am a college student learning web development.</p>\n</body>\n</html>'),

(7, 2, 'HTML Text and Formatting',
'HTML provides many tags for text: h1-h6 for headings, p for paragraphs, strong/b for bold, em/i for italic, u for underline, br for line breaks, hr for horizontal rule, small, big, sup, sub, and more.',
'<h1>Heading 1</h1>\n<p>Paragraph</p>\n<strong>Bold</strong>\n<em>Italic</em>\n<br> <!-- line break -->\n<hr> <!-- horizontal line -->',
'<!DOCTYPE html>\n<html>\n<body>\n  <h1>Main Heading</h1>\n  <h2>Sub Heading</h2>\n  <p>This is a <strong>bold</strong> word and this is <em>italic</em>.</p>\n  <p>This has a<br>line break.</p>\n  <hr>\n  <p><small>Small text</small> and normal text.</p>\n</body>\n</html>',
'Create a page with your biography using different text formatting tags.',
'Use h1 for name, h2 for sections, p for content, strong for emphasis.',
'<!DOCTYPE html>\n<html>\n<body>\n  <h1>Siya Sharma</h1>\n  <h2>About Me</h2>\n  <p>I am a <strong>Computer Science</strong> student at <em>ABC College</em>.</p>\n  <h2>Hobbies</h2>\n  <p>I enjoy <u>coding</u>, reading, and music.</p>\n</body>\n</html>'),

(7, 3, 'HTML Links and Images',
'Links use the <a> tag with href attribute. Images use <img> tag with src and alt attributes. The alt attribute provides text when image cannot load. Use target="_blank" to open link in new tab.',
'<a href="url">Link Text</a>\n<img src="image.jpg" alt="description" width="200">',
'<!DOCTYPE html>\n<html>\n<body>\n  <a href="https://www.google.com">Visit Google</a><br>\n  <a href="about.html">About Page</a><br>\n  <img src="https://via.placeholder.com/200" alt="Placeholder Image" width="200"><br>\n  <a href="page.html" target="_blank">Open in New Tab</a>\n</body>\n</html>',
'Create a page with 3 links to your favorite websites and one image.',
'Use <a href="URL">Text</a> for links and <img src="URL" alt="desc"> for image.',
'<!DOCTYPE html>\n<html>\n<body>\n  <h2>My Favorite Sites</h2>\n  <a href="https://www.google.com">Google</a><br>\n  <a href="https://www.github.com">GitHub</a><br>\n  <a href="https://www.youtube.com">YouTube</a><br>\n  <img src="https://via.placeholder.com/300x200" alt="My Image">\n</body>\n</html>'),

(7, 4, 'HTML Lists and Tables',
'Lists: ul (unordered, bullet points), ol (ordered, numbered), li (list item). Tables: table, tr (row), th (header cell), td (data cell). Use border attribute or CSS to style tables.',
'<ul><li>Item</li></ul>\n<ol><li>Item</li></ol>\n<table>\n  <tr><th>Head</th></tr>\n  <tr><td>Data</td></tr>\n</table>',
'<!DOCTYPE html>\n<html>\n<body>\n  <h2>Programming Languages</h2>\n  <ul>\n    <li>Python</li>\n    <li>JavaScript</li>\n    <li>Java</li>\n  </ul>\n  \n  <h2>Student Table</h2>\n  <table border="1">\n    <tr><th>Name</th><th>Marks</th></tr>\n    <tr><td>Alice</td><td>95</td></tr>\n    <tr><td>Bob</td><td>87</td></tr>\n  </table>\n</body>\n</html>',
'Create a table showing 5 courses, their credits, and your expected grades.',
'Use <table border="1"> then <tr> for each row and <td> for each cell.',
'<!DOCTYPE html>\n<html>\n<body>\n  <table border="1">\n    <tr><th>Course</th><th>Credits</th><th>Grade</th></tr>\n    <tr><td>Math</td><td>4</td><td>A</td></tr>\n    <tr><td>Physics</td><td>3</td><td>B</td></tr>\n    <tr><td>CS101</td><td>4</td><td>A</td></tr>\n    <tr><td>English</td><td>2</td><td>A</td></tr>\n    <tr><td>Python</td><td>4</td><td>A</td></tr>\n  </table>\n</body>\n</html>'),

(7, 5, 'HTML Forms',
'Forms collect user input. The form tag wraps input elements. input has many types: text, password, email, number, checkbox, radio, submit. label is used to describe inputs. action sets where form data is sent. method sets GET or POST.',
'<form action="submit.php" method="POST">\n  <input type="text" name="username">\n  <input type="submit" value="Submit">\n</form>',
'<!DOCTYPE html>\n<html>\n<body>\n  <form action="#" method="POST">\n    <label>Name: <input type="text" name="name" required></label><br><br>\n    <label>Email: <input type="email" name="email"></label><br><br>\n    <label>Password: <input type="password" name="pass"></label><br><br>\n    <label>Gender:\n      <input type="radio" name="gender" value="M"> Male\n      <input type="radio" name="gender" value="F"> Female\n    </label><br><br>\n    <input type="submit" value="Register">\n  </form>\n</body>\n</html>',
'Create a login form with username and password fields plus a submit button.',
'Use input type="text" for username, type="password" for password, type="submit" for button.',
'<!DOCTYPE html>\n<html>\n<body>\n  <h2>Login</h2>\n  <form action="#" method="POST">\n    <label>Username: <input type="text" name="username" required></label><br><br>\n    <label>Password: <input type="password" name="password" required></label><br><br>\n    <input type="submit" value="Login">\n  </form>\n</body>\n</html>'),

(7, 6, 'HTML Semantic Elements',
'HTML5 introduced semantic elements that describe their meaning: header, nav, main, section, article, aside, footer. These improve code readability, SEO, and accessibility. Use div for non-semantic containers.',
'<header>Top</header>\n<nav>Navigation</nav>\n<main>\n  <section>Content</section>\n  <aside>Sidebar</aside>\n</main>\n<footer>Bottom</footer>',
'<!DOCTYPE html>\n<html>\n<body>\n  <header>\n    <h1>My Website</h1>\n    <nav><a href="#">Home</a> | <a href="#">About</a></nav>\n  </header>\n  <main>\n    <section>\n      <h2>Welcome</h2>\n      <p>Main content goes here.</p>\n    </section>\n    <aside>\n      <h3>Sidebar</h3>\n      <p>Extra info here.</p>\n    </aside>\n  </main>\n  <footer>\n    <p>&copy; 2024 My Website</p>\n  </footer>\n</body>\n</html>',
'Create a full page layout using semantic HTML5 elements with header, nav, main, and footer.',
'Wrap your page parts in semantic tags: header for top, main for content, footer for bottom.',
'<!DOCTYPE html>\n<html>\n<head><title>Portfolio</title></head>\n<body>\n  <header><h1>Siya Portfolio</h1></header>\n  <nav><a href="#about">About</a> | <a href="#projects">Projects</a></nav>\n  <main>\n    <section id="about"><h2>About Me</h2><p>CS Student</p></section>\n    <section id="projects"><h2>Projects</h2><p>Web App</p></section>\n  </main>\n  <footer><p>Contact: siya@email.com</p></footer>\n</body>\n</html>'),

(7, 7, 'HTML Interview Prep',
'Key HTML interview topics: semantic HTML, accessibility (ARIA), meta tags, HTML5 APIs, Canvas, SVG, Web Storage, and best practices. Understand the DOM structure and how browsers parse HTML.',
'<!-- SEO Meta Tags -->\n<meta charset="UTF-8">\n<meta name="viewport" content="width=device-width, initial-scale=1.0">\n<meta name="description" content="Page description">',
'<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <meta name="description" content="My portfolio website">\n  <title>Portfolio</title>\n</head>\n<body>\n  <!-- Accessible form -->\n  <form>\n    <label for="email">Email:</label>\n    <input type="email" id="email" name="email" aria-required="true" placeholder="you@example.com">\n    <button type="submit">Submit</button>\n  </form>\n  \n  <!-- Data attributes -->\n  <div data-user-id="123" data-role="admin">Admin Panel</div>\n</body>\n</html>',
'Create a complete HTML5 page with proper meta tags, semantic structure, and an accessible contact form.',
'Include DOCTYPE, charset meta, viewport meta, semantic tags, and labels linked to inputs with for/id.',
'<!DOCTYPE html>\n<html lang="en">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Contact</title>\n</head>\n<body>\n  <header><h1>Contact Us</h1></header>\n  <main>\n    <form>\n      <label for="name">Name:</label>\n      <input type="text" id="name" name="name" required><br>\n      <label for="email">Email:</label>\n      <input type="email" id="email" name="email" required><br>\n      <label for="msg">Message:</label>\n      <textarea id="msg" name="message"></textarea><br>\n      <button type="submit">Send</button>\n    </form>\n  </main>\n  <footer><p>&copy; 2024</p></footer>\n</body>\n</html>');

-- ============================================================
-- LESSONS: CSS
-- ============================================================
INSERT INTO lessons (language_id, level_id, title, theory, syntax_example, code_example, practice_problem, hint, solution) VALUES
(8, 1, 'CSS Basics',
'CSS (Cascading Style Sheets) is used to style HTML elements. CSS rules have a selector and declarations. A declaration has a property and a value. CSS can be inline, internal (in <style> tag), or external (in a .css file). External CSS is best practice.',
'selector {\n  property: value;\n}',
'/* External/Internal CSS */\nbody {\n  background-color: #f0f0f0;\n  font-family: Arial, sans-serif;\n}\n\nh1 {\n  color: #333333;\n  font-size: 32px;\n}\n\np {\n  color: #666666;\n  line-height: 1.6;\n}',
'Style a page to have a light blue background, a dark heading, and gray paragraph text.',
'Target body for background-color, h1 for color, and p for color. Use hex codes.',
'body { background-color: #e8f4f8; }\nh1 { color: #1a1a2e; font-size: 36px; }\np { color: #555555; line-height: 1.8; }'),

(8, 2, 'CSS Selectors and Properties',
'CSS has many selectors: element (p), class (.classname), ID (#idname), attribute ([type="text"]), pseudo-class (:hover, :first-child), and combinators (div p, div > p). The cascade means later rules override earlier ones.',
'.class { }\n#id { }\nelement { }\nelement:hover { }\n.parent > .child { }',
'/* Class selector */\n.highlight {\n  background-color: yellow;\n  font-weight: bold;\n}\n\n/* ID selector */\n#header {\n  background: #333;\n  color: white;\n  padding: 20px;\n}\n\n/* Hover effect */\na:hover {\n  color: red;\n  text-decoration: underline;\n}',
'Create CSS to make buttons turn green with white text when hovered.',
'Use .button selector for default style. Use .button:hover for hover effect.',
'.button {\n  background-color: #007bff;\n  color: white;\n  padding: 10px 20px;\n  border: none;\n  cursor: pointer;\n}\n.button:hover {\n  background-color: #28a745;\n  color: white;\n}'),

(8, 3, 'CSS Box Model',
'Every HTML element is a rectangular box. The CSS Box Model consists of: content, padding (inside border), border, and margin (outside border). Use box-sizing: border-box to make sizing more intuitive.',
'div {\n  width: 200px;\n  padding: 20px;\n  border: 2px solid black;\n  margin: 10px;\n}',
'.card {\n  width: 300px;\n  padding: 20px;\n  border: 1px solid #ddd;\n  border-radius: 8px;\n  margin: 15px;\n  box-shadow: 0 2px 10px rgba(0,0,0,0.1);\n  box-sizing: border-box;\n}\n\n.card:hover {\n  box-shadow: 0 5px 20px rgba(0,0,0,0.2);\n  transform: translateY(-2px);\n}',
'Create a profile card with name, role, and a colored border using the box model.',
'Give the card div padding, a border, border-radius for rounded corners, and a box-shadow.',
'.profile-card {\n  width: 250px;\n  padding: 25px;\n  border: 2px solid #4A90E2;\n  border-radius: 12px;\n  text-align: center;\n  margin: 20px auto;\n  box-shadow: 0 4px 15px rgba(74,144,226,0.3);\n}'),

(8, 4, 'CSS Layouts - Flexbox',
'Flexbox is a one-dimensional layout system. Set display: flex on the container. Use flex-direction, justify-content, align-items to control layout. flex-wrap controls wrapping. gap adds space between items.',
'.container {\n  display: flex;\n  justify-content: center;\n  align-items: center;\n  gap: 20px;\n}',
'.nav {\n  display: flex;\n  justify-content: space-between;\n  align-items: center;\n  padding: 0 20px;\n  background: #333;\n}\n\n.nav a {\n  color: white;\n  text-decoration: none;\n  padding: 10px 15px;\n}\n\n.cards {\n  display: flex;\n  flex-wrap: wrap;\n  gap: 20px;\n  padding: 20px;\n}',
'Create a navigation bar using flexbox with logo on the left and links on the right.',
'Use display:flex and justify-content:space-between on the nav. Links in an inner div.',
'.navbar {\n  display: flex;\n  justify-content: space-between;\n  align-items: center;\n  padding: 15px 30px;\n  background: #2c3e50;\n  color: white;\n}\n.nav-links a {\n  color: white;\n  text-decoration: none;\n  margin-left: 20px;\n}'),

(8, 5, 'CSS Grid',
'CSS Grid is a two-dimensional layout system for rows AND columns. Set display: grid on the container. Use grid-template-columns and grid-template-rows to define structure. grid-gap adds spacing.',
'.container {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  gap: 20px;\n}',
'.portfolio-grid {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  grid-template-rows: auto;\n  gap: 20px;\n  padding: 20px;\n}\n\n.portfolio-item {\n  background: #f5f5f5;\n  border-radius: 8px;\n  padding: 20px;\n  text-align: center;\n}\n\n/* Responsive: 2 columns on medium */\n@media (max-width: 768px) {\n  .portfolio-grid { grid-template-columns: repeat(2, 1fr); }\n}',
'Create a 2x3 grid layout for a photo gallery with equal-sized cells.',
'Use display:grid, grid-template-columns: repeat(3, 1fr) for 3 columns.',
'.gallery {\n  display: grid;\n  grid-template-columns: repeat(3, 1fr);\n  grid-template-rows: repeat(2, 200px);\n  gap: 10px;\n}\n.gallery-item {\n  background: #ddd;\n  display: flex;\n  align-items: center;\n  justify-content: center;\n  border-radius: 6px;\n}'),

(8, 6, 'CSS Animations and Transitions',
'Transitions create smooth changes between states (e.g., on hover). Animations use @keyframes to define multi-step animations. Use animation-name, animation-duration, animation-iteration-count properties.',
'/* Transition */\n.btn { transition: all 0.3s ease; }\n\n/* Animation */\n@keyframes slidein {\n  from { opacity: 0; }\n  to { opacity: 1; }\n}',
'/* Hover animation */\n.card {\n  transition: transform 0.3s ease, box-shadow 0.3s ease;\n}\n.card:hover {\n  transform: scale(1.05);\n  box-shadow: 0 10px 30px rgba(0,0,0,0.2);\n}\n\n/* Pulse animation */\n@keyframes pulse {\n  0% { transform: scale(1); }\n  50% { transform: scale(1.1); }\n  100% { transform: scale(1); }\n}\n.pulse { animation: pulse 2s infinite; }',
'Create a button that spins 360 degrees when hovered using CSS transition.',
'Use transform: rotate(360deg) on hover and transition: transform 0.5s on the button.',
'.spin-btn {\n  transition: transform 0.5s ease;\n  cursor: pointer;\n}\n.spin-btn:hover {\n  transform: rotate(360deg);\n}'),

(8, 7, 'CSS Interview Prep',
'Key CSS interview topics: specificity, the cascade, responsive design, CSS variables, preprocessors (Sass basics), performance optimization, cross-browser compatibility, and modern features like clamp(), container queries.',
'/* CSS Variables */\n:root {\n  --primary: #4A90E2;\n  --font-size: 16px;\n}\n.element {\n  color: var(--primary);\n  font-size: var(--font-size);\n}',
'/* Full responsive layout */\n:root {\n  --primary: #6C63FF;\n  --secondary: #FF6584;\n}\n\n* { box-sizing: border-box; margin: 0; padding: 0; }\n\nbody {\n  font-family: "Segoe UI", sans-serif;\n  background: var(--background, #f4f4f4);\n}\n\n.container {\n  max-width: 1200px;\n  margin: 0 auto;\n  padding: 0 15px;\n}\n\n@media (max-width: 768px) {\n  .grid { grid-template-columns: 1fr; }\n  .sidebar { display: none; }\n}',
'Write a CSS reset stylesheet and responsive breakpoints for mobile, tablet, and desktop.',
'Reset: set margin/padding to 0 for all. Breakpoints: @media (max-width: 576px), 768px, 992px.',
'/* CSS Reset */\n* {\n  margin: 0;\n  padding: 0;\n  box-sizing: border-box;\n}\nbody { font-family: sans-serif; line-height: 1.6; }\n\n/* Mobile */\n@media (max-width: 576px) {\n  .container { padding: 0 10px; }\n}\n/* Tablet */\n@media (max-width: 768px) {\n  .grid { grid-template-columns: 1fr 1fr; }\n}\n/* Desktop */\n@media (min-width: 992px) {\n  .grid { grid-template-columns: repeat(3, 1fr); }\n}');

-- Update admin password to proper bcrypt hash for Sss@2005
UPDATE admin SET password = '$2y$10$TKh8H1.PfzCT6UMQ/I8/TuKFX3mEa.eS3qf5rK6aVbfQSNu3vD6E.' WHERE username = 'Admin@123';
