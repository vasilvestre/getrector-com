<?php

declare(strict_types=1);

namespace App\Tests\RuleFilter;

use App\RuleFilter\MatchingScoreResolver;
use App\RuleFilter\ValueObject\RuleMetadata;
use App\Tests\AbstractTestCase;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;

final class MatchingScoreResolverTest extends AbstractTestCase
{
    public function testDescriptionSearchIsCaseInsensitive(): void
    {
        $matchingScoreResolver = $this->make(MatchingScoreResolver::class);
        
        $ruleMetadata = new RuleMetadata(
            RenameMethodRector::class,
            'Rename specific method calls to new ones',
            [],
            [],
            'some-rector.php'
        );
        
        // Test that lowercase query matches uppercase description
        $scoreLowercase = $matchingScoreResolver->resolve($ruleMetadata, 'method');
        $scoreUppercase = $matchingScoreResolver->resolve($ruleMetadata, 'METHOD');
        $scoreMixedCase = $matchingScoreResolver->resolve($ruleMetadata, 'Method');
        
        // All should have the same score since search should be case insensitive
        $this->assertSame($scoreLowercase, $scoreUppercase);
        $this->assertSame($scoreLowercase, $scoreMixedCase);
        $this->assertTrue($scoreLowercase > 0, 'Score should be greater than 0 when description contains search term');
    }
    
    public function testDescriptionSearchWithMultipleWords(): void
    {
        $matchingScoreResolver = $this->make(MatchingScoreResolver::class);
        
        $ruleMetadata = new RuleMetadata(
            RenameMethodRector::class,
            'Rename Specific Method Calls To New Ones',
            [],
            [],
            'some-rector.php'
        );
        
        // Test mixed case multi-word search
        $scoreOriginal = $matchingScoreResolver->resolve($ruleMetadata, 'specific method');
        $scoreMixedCase = $matchingScoreResolver->resolve($ruleMetadata, 'Specific Method');
        $scoreUppercase = $matchingScoreResolver->resolve($ruleMetadata, 'SPECIFIC METHOD');
        
        // All should have the same score
        $this->assertSame($scoreOriginal, $scoreMixedCase);
        $this->assertSame($scoreOriginal, $scoreUppercase);
        $this->assertTrue($scoreOriginal > 0, 'Score should be greater than 0 when description contains search terms');
    }
    
    public function testRenameQueryShouldFindRenameClassRector(): void
    {
        $matchingScoreResolver = $this->make(MatchingScoreResolver::class);
        
        // This is the failing case mentioned by @vasilvestre
        // Searching for "rename" should show "RenameClass" as it starts with "rename"
        // The issue is about the rule "title" or "name" (getRuleShortClass()) shown in UI
        $ruleMetadata = new RuleMetadata(
            'Rector\Renaming\Rector\Class_\RenameClassRector',
            'Some unrelated description without the search term',
            [],
            [],
            'some-rector.php'
        );
        
        // The rule title/name displayed in UI is: getRuleShortClass() = "RenameClassRector"
        // When searching for "rename", this should be found because "RenameClassRector" starts with "rename"
        $scoreLowercase = $matchingScoreResolver->resolve($ruleMetadata, 'rename');
        $scoreUppercase = $matchingScoreResolver->resolve($ruleMetadata, 'RENAME');
        $scoreMixedCase = $matchingScoreResolver->resolve($ruleMetadata, 'Rename');
        
        // All should have the same positive score since "RenameClassRector" starts with "rename"
        $this->assertSame($scoreLowercase, $scoreUppercase);
        $this->assertSame($scoreLowercase, $scoreMixedCase);
        $this->assertTrue($scoreLowercase > 0, 'Score should be greater than 0 when rule name/title starts with search term "rename"');
        
        // Additional verification: the score should be high since rule name starts with the search term
        $this->assertGreaterThanOrEqual(10, $scoreLowercase, 'Score should be at least 10 for rule name starting with the search term');
    }
    
    public function testRuleNameTitleMatchingIsCaseInsensitive(): void
    {
        $matchingScoreResolver = $this->make(MatchingScoreResolver::class);
        
        // Test a rule whose name/title starts with different cases
        $ruleMetadata = new RuleMetadata(
            'SomeNamespace\RenameClass',  // Short class name will be "RenameClass"
            'Description without search term',
            [],
            [],
            'some-rule.php'
        );
        
        // Test that all case variations find the rule based on its title/name
        $scoreLower = $matchingScoreResolver->resolve($ruleMetadata, 'rename');
        $scoreUpper = $matchingScoreResolver->resolve($ruleMetadata, 'RENAME');
        $scoreMixed = $matchingScoreResolver->resolve($ruleMetadata, 'Rename');
        
        // All should have the same score
        $this->assertSame($scoreLower, $scoreUpper);
        $this->assertSame($scoreLower, $scoreMixed);
        $this->assertTrue($scoreLower > 0, 'Should find rule when title/name starts with search term');
    }
    
    public function testNoMatchReturnsZero(): void
    {
        $matchingScoreResolver = $this->make(MatchingScoreResolver::class);
        
        $ruleMetadata = new RuleMetadata(
            RenameMethodRector::class,
            'Rename specific method calls',
            [],
            [],
            'some-rector.php'
        );
        
        $score = $matchingScoreResolver->resolve($ruleMetadata, 'nonexistent');
        $this->assertSame(0, $score);
    }
}