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