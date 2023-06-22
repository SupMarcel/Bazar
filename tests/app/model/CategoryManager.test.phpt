<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/Tests/NetteTest.phpt to edit this template
 */

require 'Tester/bootstrap.php';

use Tester\Assert;

$object = new Greeting();

// use an assertion function to test say()
Assert::same('Hello John', $object->say('John'));
