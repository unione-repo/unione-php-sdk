<?php

declare(strict_types=1);

namespace UniOne\Api\Email\Model;

use UniOne\Client\Data;

/**
 * MessageObjectRecipientsItem
 */
final class MessageObjectRecipientsItem implements \JsonSerializable
{
    public function __construct(
        /**
         * Recipient email
         */
        public string $email,
        /**
         * Object to pass the substitutions(merge tags) for the recipient (e.g. recipient name, discount code, password
         * change link, etc. See [Template engines](https://docs.unione.io/en/template-engines)). The substitutions can
         * be used in the following parameters:
         *
         * - body.html
         * - body.plaintext
         * - body.amp
         * - subject
         * - from_name
         * - headers["List-Unsubscribe"]
         * - options.unsubscribe_url
         * A substitution name can consist from latin characters, numbers and "_" symbol, and should start with the
         * letter. There's a special substitution "to_name" which is used to put recipent's name like "Name Surname" to
         * include it to SMTP header "To" in the form "Name Surname <email@example.com>". The "to_name" length is limited
         * to 78 symbols.
         * @var array<string, string>|null
         */
        public ?array $substitutions = null,
        /**
         * Object for passing the metadata, such as "key": "value". Max key quantity: 10. Max key length: 64 symbols. Max
         * value length: 1024 symbols. The metadata is returned by
         * [webhook](https://docs.unione.io/en/web-api-ref#webhook) and
         * [event-dump](https://docs.unione.io/en/web-api-ref#event-dump) methods. If you pass strign up to 128 bit with
         * "campaign_id" field name (name is configurable thru support), it will be considered as campaign identifier in
         * statistics.
         * @var array<string, string>|null
         */
        public ?array $metadata = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data Decoded JSON.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: Data::string($data['email'] ?? null) ?? '',
            substitutions: Data::map($data['substitutions'] ?? null, static fn ($item) => Data::string($item) ?? ''),
            metadata: Data::map($data['metadata'] ?? null, static fn ($item) => Data::string($item) ?? ''),
        );
    }

    /**
     * A recipient from "email" or "Name <email>": the name goes to the to_name substitution (the name in the "To"
     * header).
     *
     * @param array<string, string>|null $substitutions More substitutions of the recipient.
     */
    public static function fromAddress(string $address, ?array $substitutions = null): self
    {
        $email = \trim($address);
        $name = '';
        if (\preg_match('/^(.*)<([^>]+)>$/s', $email, $match) === 1) {
            $email = \trim($match[2]);
            $name = \trim(\trim($match[1]), '"');
        }
        if ($name !== '') {
            $substitutions = ($substitutions ?? []) + ['to_name' => $name];  // an explicit to_name wins
        }

        return new self(email: $email, substitutions: $substitutions);
    }

    /**
     * Sets the metadata of the recipient (returned in the webhook events of its email); fluent.
     *
     * @param array<string, string> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * @return array<string, mixed> JSON-ready array: null fields are omitted, empty objects are \stdClass.
     */
    public function toArray(): array
    {
        return \array_filter([
            'email' => $this->email,
            'substitutions' => Data::object($this->substitutions),
            'metadata' => Data::object($this->metadata),
        ], static fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>|\stdClass JSON object: {} when empty.
     */
    public function jsonSerialize(): mixed
    {
        return Data::object($this->toArray());
    }
}
