<?php

declare(strict_types=1);

namespace UniOne\Common\Model;

use UniOne\Client\Data;

/**
 * Contains HTML/plaintext/AMP parts of the email. Either html or plaintext part is required.
 */
final class BodyObject implements \JsonSerializable
{
    /**
     * HTML part of the email body
     * Example: `<b>Hello, &#7b;&#7b;to_name&#7c;&#7c;</b>`.
     *
     * @var string|null
     */
    private $html;

    /**
     * Plaintext part of the email body.
     * Example: `Hello, &#7b;&#7b;to_name&#7c;&#7c;`.
     *
     * @var string|null
     */
    private $plaintext;

    /**
     * Optional [AMP](http://amp.dev/documentation/guides-and-tutorials/learn/email_fundamentals/) part of the email
     * body.
     * Example: `<!doctype html><html amp4email><head> <meta charset="utf-8"><script async
     * src="https://cdn.ampproject.org/v0.js"></script> <style
     * amp4email-boilerplate>body{visibility:hidden}</style></head><body> Hello, AMP4EMAIL world.</body></html>`.
     *
     * @var string|null
     */
    private $amp;

    /**
     * @param array<string, mixed> $data Decoded JSON.
     */
    public static function fromArray(array $data): self
    {
        $model = new self();
        $model->html = Data::string($data['html'] ?? null);
        $model->plaintext = Data::string($data['plaintext'] ?? null);
        $model->amp = Data::string($data['amp'] ?? null);

        return $model;
    }

    /**
     * HTML part of the email body
     */
    public function getHtml(): ?string
    {
        return $this->html;
    }

    /**
     * HTML part of the email body
     *
     * @return $this
     */
    public function setHtml(?string $html): self
    {
        $this->html = $html;

        return $this;
    }

    /**
     * Plaintext part of the email body.
     */
    public function getPlaintext(): ?string
    {
        return $this->plaintext;
    }

    /**
     * Plaintext part of the email body.
     *
     * @return $this
     */
    public function setPlaintext(?string $plaintext): self
    {
        $this->plaintext = $plaintext;

        return $this;
    }

    /**
     * Optional [AMP](http://amp.dev/documentation/guides-and-tutorials/learn/email_fundamentals/) part of the email
     * body.
     */
    public function getAmp(): ?string
    {
        return $this->amp;
    }

    /**
     * Optional [AMP](http://amp.dev/documentation/guides-and-tutorials/learn/email_fundamentals/) part of the email
     * body.
     *
     * @return $this
     */
    public function setAmp(?string $amp): self
    {
        $this->amp = $amp;

        return $this;
    }

    /**
     * An HTML body, with an optional plain-text part.
     */
    public static function html(string $html, ?string $plaintext = null): self
    {
        $body = (new self())->setHtml($html);
        if ($plaintext !== null) {
            $body->setPlaintext($plaintext);
        }

        return $body;
    }

    /**
     * A plain-text body.
     */
    public static function text(string $plaintext): self
    {
        return (new self())->setPlaintext($plaintext);
    }

    /**
     * @return array<string, mixed> JSON-ready array: null fields are omitted, empty objects are \stdClass.
     */
    public function toArray(): array
    {
        return \array_filter([
            'html' => $this->html,
            'plaintext' => $this->plaintext,
            'amp' => $this->amp,
        ], static function ($value): bool {
            return $value !== null;
        });
    }

    /**
     * @return array<string, mixed>|\stdClass JSON object: {} when empty.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return Data::object($this->toArray());
    }
}
