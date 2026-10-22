#!/usr/bin/env python
# ============================================================
# ai_feedback.py - Rule-Based NLP AI Feedback System
# GamifiedLearningApp - MCA Major Project
# No external libraries required
# Usage: python ai_feedback.py <json_file_path>
# ============================================================

import sys
import json
import re
import os

# ============================================================
# COMPILE ERROR PATTERNS
# ============================================================
COMPILE_PATTERNS = [
    # C / C++
    (r"expected\s*[';']", "🔴 Missing semicolon (;) at end of a statement. Check all your lines ending with a statement."),
    (r"expected\s*'\)'|expected\s*'\('", "🔴 Missing bracket. Check all your opening '(' and closing ')' brackets are balanced."),
    (r"expected\s*'}'|expected\s*'{'", "🔴 Missing curly brace. Check all your opening '{' and closing '}' braces are balanced."),
    (r"undeclared\s+identifier|'.*?' was not declared", "🔴 You used a variable or function that was never declared. Declare it before using it."),
    (r"undefined reference to", "🔴 Undefined reference: A function you called doesn't exist or wasn't included. Check your function name or #include."),
    (r"implicit declaration of function", "🔴 You called a function before declaring it. Add the correct #include at the top."),
    (r"error: expected declaration", "🔴 Something is written outside of a function. All code must be inside functions like main()."),
    (r"too few arguments|too many arguments", "🔴 Wrong number of arguments in a function call. Check the function definition and match the parameters."),
    (r"incompatible types|cannot convert", "🔴 Type mismatch error. You are assigning or using a value of the wrong data type."),
    (r"redefinition of|already defined", "🔴 You defined the same variable or function twice. Remove or rename the duplicate."),
    (r"no return statement", "🔴 Your function is missing a return statement. Add 'return' at the end of the function."),

    # Java
    (r"cannot find symbol", "🔴 Java cannot find a variable, method, or class. Check spelling and make sure it is declared."),
    (r"reached end of file while parsing", "🔴 Java could not find closing '}'. Count your opening and closing curly braces."),
    (r"class .+ is public, should be declared in a file named", "🔴 Java class name must match the filename exactly. Your class must be named 'Main'."),
    (r"illegal start of expression", "🔴 Something is written in the wrong place. Check for extra brackets, braces, or symbols."),
    (r"not a statement", "🔴 Invalid statement found. Check for typos or misplaced operators."),
    (r"unclosed string literal", "🔴 A string is not closed. Make sure every opening quote \" has a matching closing quote \"."),

    # Python
    (r"IndentationError|unexpected indent|expected an indented block", "🔴 Indentation Error: Python requires consistent spacing (use 4 spaces or 1 tab). Check your indentation."),
    (r"SyntaxError: invalid syntax", "🔴 Syntax Error: Something is written incorrectly. Check for missing colons (:), brackets, or quotes."),
    (r"SyntaxError: EOL while scanning string literal", "🔴 A string is not closed. Make sure every opening quote has a matching closing quote."),
    (r"NameError: name '(.+)' is not defined", "🔴 NameError: Variable or function '{0}' is used before it is defined. Check spelling or declare it first."),
    (r"SyntaxError: cannot assign to", "🔴 Invalid assignment. You may be using '==' instead of '=' or assigning to a literal value."),

    # PHP
    (r"Parse error: syntax error", "🔴 PHP Syntax Error: Check for missing semicolons (;), mismatched brackets, or unclosed strings."),
    (r"unexpected token", "🔴 Unexpected token found. Something is written in the wrong place — check symbols and keywords."),

    # General
    (r"error C\d+:", "🔴 Compiler error found. Read the error message carefully — the line number tells you exactly where to look."),
    (r"error:", "🔴 Compilation failed. Read the error message — it contains the line number and the exact problem."),
]

# ============================================================
# RUNTIME ERROR PATTERNS
# ============================================================
RUNTIME_PATTERNS = [
    (r"division by zero|ZeroDivisionError|divide by zero|Floating point exception", "⚠️ Division by Zero: You are dividing a number by 0. Add a check: if denominator != 0 before dividing."),
    (r"IndexError: list index out of range|ArrayIndexOutOfBoundsException|index out of range|array index", "⚠️ Index Out of Range: You are accessing an array/list at a position that doesn't exist. Check your loop limits and array size."),
    (r"NullPointerException|null pointer|None.*has no attribute|NoneType", "⚠️ Null/None Error: You are using a variable that is None/null. Make sure the variable is initialized before using it."),
    (r"Segmentation fault|SIGSEGV|segfault", "⚠️ Segmentation Fault: Your program accessed memory it should not. Check array bounds and pointer usage."),
    (r"StackOverflowError|maximum recursion depth|RecursionError", "⚠️ Stack Overflow: Infinite recursion detected. Make sure your recursive function has a proper base case to stop."),
    (r"FileNotFoundError|No such file or directory", "⚠️ File Not Found: The file your program is looking for does not exist. Check the filename and path."),
    (r"ValueError", "⚠️ Value Error: You passed an incorrect value to a function. Check the type and range of your input."),
    (r"TypeError", "⚠️ Type Error: You are using a value of the wrong type. Check if you need to convert between int, string, float etc."),
    (r"KeyError", "⚠️ Key Error: You accessed a dictionary key that doesn't exist. Check if the key exists before accessing it."),
    (r"AttributeError", "⚠️ Attribute Error: You called a method or property that doesn't exist on this object. Check the variable type."),
    (r"MemoryError|OutOfMemoryError", "⚠️ Memory Error: Your program used too much memory. Check for infinite loops or very large data structures."),
    (r"TimeoutError|time limit exceeded|TLE", "⚠️ Time Limit Exceeded: Your program ran too long. Check for infinite loops — make sure your loop has a proper stopping condition."),
    (r"ImportError|ModuleNotFoundError", "⚠️ Import Error: A module you imported does not exist. Check the module name spelling."),
    (r"Exception in thread|RuntimeException", "⚠️ Runtime Exception in Java: An unexpected error occurred during execution. Read the error details carefully."),
]

# ============================================================
# WRONG ANSWER SUGGESTIONS
# ============================================================
def wrong_answer_feedback(user_output, expected_output, language):
    suggestions = []
    
    # Check trailing/leading spaces
    if user_output.strip() == expected_output.strip() and user_output != expected_output:
        return "💡 Very close! Your output has extra spaces or newlines. Use strip() or trim() to clean your output."

    # Check case sensitivity
    if user_output.lower() == expected_output.lower():
        return "💡 Almost correct! Your output has wrong capitalization. Check uppercase/lowercase letters carefully."

    # Check extra newlines
    if user_output.replace('\n', '') == expected_output.replace('\n', ''):
        return "💡 Your logic is correct but output has extra or missing newlines. Check your print statements."

    # Check if output is a number comparison
    try:
        if float(user_output.strip()) == float(expected_output.strip()):
            return "💡 The numeric value is correct but the format is wrong. Check if you need more/fewer decimal places."
    except (ValueError, TypeError):
        pass

    # Check if expected is empty
    if not expected_output.strip():
        return "💡 No output expected for this test case but your program printed something. Check if there should be any output."

    # Check if output is completely empty
    if not user_output.strip():
        return "💡 Your program produced no output. Make sure your print/cout/System.out.println statement is executing correctly."

    # Check output length
    out_lines = user_output.strip().split('\n')
    exp_lines = expected_output.strip().split('\n')
    
    if len(out_lines) < len(exp_lines):
        return "💡 Wrong Answer: Your output has fewer lines than expected. Check if all print statements are being reached."
    
    if len(out_lines) > len(exp_lines):
        return "💡 Wrong Answer: Your output has more lines than expected. You may have an extra print statement."

    # General suggestions
    suggestions = [
        "💡 Wrong Answer — Here are some things to check:",
        "   → Re-read the problem statement carefully.",
        "   → Check your loop condition (off-by-one errors are common).",
        "   → Check output format: spaces, newlines, and punctuation matter.",
        "   → Trace through your code step by step with the sample input.",
        "   → Make sure you are reading all inputs correctly.",
    ]
    return '\n'.join(suggestions)

# ============================================================
# LANGUAGE-SPECIFIC TIPS
# ============================================================
def language_tip(language):
    tips = {
        'python':     "💬 Python Tip: Remember Python uses indentation instead of brackets. Use 4 spaces consistently.",
        'java':       "💬 Java Tip: Your class must be named 'Main'. Use System.out.println() for output.",
        'c':          "💬 C Tip: Don't forget #include <stdio.h> and return 0 in main().",
        'cpp':        "💬 C++ Tip: Use #include <iostream> and 'using namespace std;' for cout/cin.",
        'javascript': "💬 JS Tip: Use console.log() for output. Check for undefined variables.",
        'php':        "💬 PHP Tip: Every statement must end with a semicolon (;). Start with <?php",
        'html':       "💬 HTML Tip: Make sure all tags are properly opened and closed.",
    }
    return tips.get(language.lower(), "")

# ============================================================
# MAIN FEEDBACK ENGINE
# ============================================================
def generate_feedback(data):
    language       = data.get('language', '').lower().strip()
    user_code      = data.get('user_code', '').strip()
    compile_error  = data.get('compile_error', '').strip()
    runtime_error  = data.get('runtime_error', '').strip()
    program_output = data.get('program_output', '').strip()
    expected_output= data.get('expected_output', '').strip()

    lines = []

    # ---- CASE A: Compile Error ----
    if compile_error:
        lines.append("═══════════════════════════════════")
        lines.append("🤖 AI FEEDBACK — COMPILE ERROR")
        lines.append("═══════════════════════════════════")
        
        matched = False
        for pattern, message in COMPILE_PATTERNS:
            m = re.search(pattern, compile_error, re.IGNORECASE)
            if m:
                # Fill in regex group if present
                try:
                    msg = message.format(*m.groups())
                except IndexError:
                    msg = message
                lines.append(msg)
                matched = True
                break
        
        if not matched:
            lines.append("🔴 Compilation failed. Read the error message carefully.")
            lines.append("   The error message shows the exact line number where the problem is.")
        
        lines.append("")
        lines.append("📋 How to Fix:")
        lines.append("   1. Look at the LINE NUMBER in the error message above.")
        lines.append("   2. Go to that line in your code.")
        lines.append("   3. Fix the issue mentioned.")
        lines.append("   4. Run again.")
        
        tip = language_tip(language)
        if tip:
            lines.append("")
            lines.append(tip)
        
        return '\n'.join(lines)

    # ---- CASE B: Runtime Error ----
    if runtime_error:
        lines.append("═══════════════════════════════════")
        lines.append("🤖 AI FEEDBACK — RUNTIME ERROR")
        lines.append("═══════════════════════════════════")
        lines.append("✅ Your code compiled successfully.")
        lines.append("❌ But it crashed while running.")
        lines.append("")
        
        matched = False
        for pattern, message in RUNTIME_PATTERNS:
            if re.search(pattern, runtime_error, re.IGNORECASE):
                lines.append(message)
                matched = True
                break
        
        if not matched:
            lines.append("⚠️ Runtime Error: Your program crashed unexpectedly.")
            lines.append("   Read the error message to find out what went wrong.")
        
        lines.append("")
        lines.append("📋 General Runtime Checks:")
        lines.append("   → Make sure all array/list accesses are within bounds.")
        lines.append("   → Check that all variables are initialized before use.")
        lines.append("   → Verify that user input is handled correctly.")
        
        return '\n'.join(lines)

    # ---- CASE C & D: Output Comparison ----
    if expected_output:
        if program_output == expected_output:
            # CASE D: Correct!
            lines.append("═══════════════════════════════════")
            lines.append("🤖 AI FEEDBACK — PERFECT!")
            lines.append("═══════════════════════════════════")
            lines.append("🎉 Congratulations! Your output matches exactly!")
            lines.append("✅ All test cases passed.")
            lines.append("🏆 Level Completed! You earned XP!")
            lines.append("")
            lines.append("👏 Keep up the great work!")
        else:
            # CASE C: Wrong Answer
            lines.append("═══════════════════════════════════")
            lines.append("🤖 AI FEEDBACK — WRONG ANSWER")
            lines.append("═══════════════════════════════════")
            lines.append("✅ Your code compiled and ran successfully.")
            lines.append("❌ But the output doesn't match the expected output.")
            lines.append("")
            lines.append(wrong_answer_feedback(program_output, expected_output, language))
        
        return '\n'.join(lines)

    # ---- Default: Code ran, no evaluation ----
    if program_output:
        lines.append("═══════════════════════════════════")
        lines.append("🤖 AI FEEDBACK")
        lines.append("═══════════════════════════════════")
        lines.append("✅ Your code ran successfully!")
        lines.append("")
        lines.append("📤 Output was produced. Click 'Submit & Evaluate' to check against test cases.")
        tip = language_tip(language)
        if tip:
            lines.append("")
            lines.append(tip)
        return '\n'.join(lines)

    return "🤖 No feedback available for this submission."

# ============================================================
# ENTRY POINT
# ============================================================
def main():
    if len(sys.argv) < 2:
        print("Usage: python ai_feedback.py <json_file_path>")
        sys.exit(1)

    json_file = sys.argv[1]

    # Security: Validate file path
    if not os.path.isfile(json_file):
        print("Error: Input file not found.")
        sys.exit(1)

    # Read JSON input
    try:
        with open(json_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except (json.JSONDecodeError, IOError) as e:
        print("Error: Could not read input file. " + str(e))
        sys.exit(1)

    # Generate and print feedback
    feedback = generate_feedback(data)
    print(feedback)

    # Auto-delete temp file
    try:
        os.remove(json_file)
    except OSError:
        pass

if __name__ == '__main__':
    main()
