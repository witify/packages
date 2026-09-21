<?php

namespace Witify\Devops\ValueObjects;

/**
 * A text carried in both portal languages, resolved on the client system so
 * the portal never has to translate a client-specific check.
 */
final class LocalizedTextData
{
    public const FRENCH = 'fr';

    public const ENGLISH = 'en';

    public string $fr;

    public string $en;

    public function __construct(string $fr, string $en)
    {
        $this->fr = $fr;
        $this->en = $en;
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    public static function fromTranslation(string $key, array $replace = []): self
    {
        return new self(
            (string) __($key, $replace, self::FRENCH),
            (string) __($key, $replace, self::ENGLISH),
        );
    }

    /**
     * @param  array<string, mixed>  $replace
     */
    public static function fromTranslationChoice(string $key, int $number, array $replace = []): self
    {
        return new self(
            trans_choice($key, $number, $replace, self::FRENCH),
            trans_choice($key, $number, $replace, self::ENGLISH),
        );
    }

    /**
     * Wraps a text that exists in a single language, such as the message of a
     * technical check, so every check exposes the same shape.
     */
    public static function fromString(?string $text): ?self
    {
        if ($text === null || $text === '') {
            return null;
        }

        return new self($text, $text);
    }

    /**
     * @return array{fr: string, en: string}
     */
    public function toArray(): array
    {
        return [
            self::FRENCH => $this->fr,
            self::ENGLISH => $this->en,
        ];
    }
}
