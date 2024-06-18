<?php
declare(strict_types=1);

namespace Connector\Type;

use Connector\Exceptions\DataConversionException;
use Connector\Exceptions\NotImplemented;
use DateTime;
use DateTimeInterface;
use IntlDateFormatter;
use NumberFormatter;

final class TypedValue
{

    public mixed $value;
    public DataType $type;

    /**
     * @throws \Exception
     */
    public function __construct(mixed $value, DataType $type = null)
    {
        if ($value instanceof TypedValue) {
            $this->value = $value->value;
            $this->type  = $value->type;
        } else {
            $this->value = $value;
            $this->type  = $this->getType($value);
            if ($type) {
                $this->type->setLocale($type->getLocale());
                $this->type->setTimeZone($type->getTimeZone());
            }
        }

        /** @noinspection PhpNonStrictObjectEqualityInspection */
        if ($type && $type != $this->type) {
            $this->convert($type);
        }
    }

    /**
     * @throws \Exception
     */
    private function getType(mixed $value): DataType
    {
        switch (gettype($value)) {
            case 'NULL':
                return new DataType(JsonSchemaTypes::Null);
            case 'string':
                return new DataType(JsonSchemaTypes::String);
            case 'integer':
                return new DataType(JsonSchemaTypes::Integer);
            case 'double':
                return new DataType(JsonSchemaTypes::Number);
            case 'boolean':
                return new DataType(JsonSchemaTypes::Boolean);
            case 'array':
                $d = new DataType(JsonSchemaTypes::Array);
                $items = [];
                foreach($value as $v) {
                    $items[] = $this->getType($v);
                }
                if(count(array_unique($items, SORT_REGULAR))===1) {
                    return $d->setItems($items[0]);
                } else {
                    return $d->setPrefixItems($items);
                }

        }
        throw new DataConversionException(sprintf("Cannot convert value of type '%s'", gettype($value)));
    }

    /**
     * @throws \Exception
     */
    public function convert(DataType $type): self
    {
        // Convert to $type
        switch ($type->type) {

            case JsonSchemaTypes::Boolean:
                $this->value = $this->toBoolean();
                $this->type  = $type;
                break;
            case JsonSchemaTypes::Number:
                $this->value = $this->toNumber($type->getLocale());
                $this->type  = $type;
                break;
            case JsonSchemaTypes::Integer:
                $this->value = $this->toInteger();
                $this->type  = $type;
                break;
            case JsonSchemaTypes::String:
                switch ($type->format) {
                    case JsonSchemaFormats::Time:
                        throw new NotImplemented();
                    case JsonSchemaFormats::Date:
                        $this->value = $this->toDate($type->getLocale(), $type->getTimeZone());
                        $this->type  = $type;
                        break;
                    case JsonSchemaFormats::DateTime:
                        $this->value = $this->toDateTime($type->getLocale(), $type->getTimeZone());
                        $this->type  = $type;
                        break;
                    case JsonSchemaFormats::LocalDate:
                        $dateType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::Date);
                        $dateType->setTimeZone($this->type->getTimeZone());
                        $dateType->setLocale($this->type->getLocale());
                        $this->convert($dateType);
                        $this->value = $this->toString($type);
                        $this->type  = $type;
                        break;
                    case JsonSchemaFormats::LocalDateTime:
                        $dateType = new DataType(JsonSchemaTypes::String, JsonSchemaFormats::DateTime);
                        $dateType->setTimeZone($this->type->getTimeZone());
                        $dateType->setLocale($this->type->getLocale());
                        $this->convert($dateType);
                        $this->value = $this->toString($type);
                        $this->type  = $type;
                        break;
                    case JsonSchemaFormats::LocalTime:
                        $this->convert(new DataType(JsonSchemaTypes::String, JsonSchemaFormats::Time));
                        $this->value = $this->toString($type);
                        $this->type  = $type;
                        break;
                    default:
                        $this->value = $this->toString($type);
                        $this->type  = $type;
                }
                break;
            case JsonSchemaTypes::Array:
                $this->value = $this->toArray($type);
                $this->type  = $type;
                break;
            default:
                throw new DataConversionException(
                    sprintf("Cannot convert '%s' to %s", $this->value, $type->type->value)
                );
        }

        return $this;
    }

    /**
     * @throws \Connector\Exceptions\DataConversionException
     */
    private function toBoolean(): bool
    {
        switch ($this->type->type) {
            case JsonSchemaTypes::String:
                switch (trim(strtolower($this->value))) {
                    case '1':
                    case 'true':
                        return true;
                    case '':
                    case 'false':
                    case '0':
                        return false;
                }
                break;
            case JsonSchemaTypes::Boolean:
                return $this->value;
            case JsonSchemaTypes::Integer:
            case JsonSchemaTypes::Number:
                return (bool)$this->value;
            case JsonSchemaTypes::Array:
                if(is_array($this->value)) {
                    if($this->type->hasItems()){
                        $sourceType = $this->type->getItems();
                    } else {
                        $sourceType = $this->type->getPrefixItems()[0];
                    }
                    $v = new TypedValue($this->value[0], $sourceType);
                    return $v->toBoolean();
                }
                break;
        }
        throw new DataConversionException(sprintf("Cannot convert '%s' to boolean", print_r($this->value,true)));
    }

    /**
     * @throws \Connector\Exceptions\DataConversionException
     */
    private function toNumber(string $locale): float
    {
        switch ($this->type->type) {
            case JsonSchemaTypes::String:
                $value = str_replace("+", "", trim($this->value));
                if($value === "") {
                    return 0.0;
                }
                $f     = new NumberFormatter($locale, NumberFormatter::DECIMAL);
                $v     = $f->parse($value);
                if ($v !== false) {
                    return (float) $v;
                }
                break;
            case JsonSchemaTypes::Boolean:
            case JsonSchemaTypes::Integer:
            case JsonSchemaTypes::Number:
                return (float)$this->value;
            case JsonSchemaTypes::Array:
                if(is_array($this->value)) {
                    if($this->type->hasItems()){
                        $sourceType = $this->type->getItems();
                    } else {
                        $sourceType = $this->type->getPrefixItems()[0];
                    }
                    $v = new TypedValue($this->value[0], $sourceType);
                    return $v->toNumber($locale);
                }
                break;
        }
        throw new DataConversionException(sprintf("Cannot convert '%s' to number", print_r($this->value,true)));
    }

    /**
     * @throws \Connector\Exceptions\DataConversionException
     */
    private function toInteger(): int
    {
        switch ($this->type->type) {
            case JsonSchemaTypes::String:
                $value = str_replace("+", "", trim($this->value));
                if($value === "") {
                    return 0;
                }
                $f     = new NumberFormatter($this->type->getLocale(), NumberFormatter::TYPE_INT32);
                $v     = $f->parse($value);
                if ($v !== false) {
                    return (int)$v;
                }
                break;
            case JsonSchemaTypes::Boolean:
            case JsonSchemaTypes::Integer:
            case JsonSchemaTypes::Number:
                return (int)$this->value;
            case JsonSchemaTypes::Array:
                if(is_array($this->value)) {
                    if($this->type->hasItems()){
                        $sourceType = $this->type->getItems();
                    } else {
                        $sourceType = $this->type->getPrefixItems()[0];
                    }
                    $v = new TypedValue($this->value[0], $sourceType);
                    return $v->toInteger();
                }
                break;
        }
        throw new DataConversionException(sprintf("Cannot convert '%s' to integer", print_r($this->value,true)));
    }

    /**
     * @throws \Connector\Exceptions\DataConversionException
     * @throws \Exception
     */
    private function toDate(string $locale, string $timeZone): string
    {
        if ($this->type->type === JsonSchemaTypes::String) {
            switch ($this->type->format) {
                case JsonSchemaFormats::Date:
                    return $this->value;
                case JsonSchemaFormats::DateTime:
                    $d = new DateTime($this->value);
                    return $d->format("Y-m-d");
                default:
                    return $this->coerceLocalizedStringToDate($this->value, $locale, $timeZone);
            }
        }
        throw new DataConversionException(sprintf("Cannot convert '%s' to a date", print_r($this->value,true)));
    }

    /**
     * @throws \Connector\Exceptions\DataConversionException
     */
    private function toDateTime(string $locale, string $timeZone): string
    {
        if ($this->type->type === JsonSchemaTypes::String) {

            switch ($this->type->format) {
                case JsonSchemaFormats::Date:
                    $d = new DateTime($this->value);
                    return $d->format(DateTimeInterface::ISO8601);
                case JsonSchemaFormats::DateTime:
                    return $this->value;
                default:
                    return $this->coerceLocalizedStringToDateTime($this->value, $locale, $timeZone);
            }
        }
        throw new DataConversionException(sprintf("Cannot convert '%s' to a date-time", print_r($this->value,true)));
    }

    /**
     * @throws \Exception
     */
    private function toArray(DataType $arrayType): array
    {
        $values = [];
        switch($this->type->type) {
            case JsonSchemaTypes::Array:
                for($i=0; $i < count($this->value); $i++) {
                    if($this->type->hasItems()){
                        $sourceType = $this->type->getItems();
                    } else {
                        $sourceType = $this->type->getPrefixItems()[$i];
                    }
                    $v = new TypedValue($this->value[$i], $sourceType);
                    if($arrayType->hasItems()) {
                        $targetType = $arrayType->getItems();
                    } else {
                        $targetType = $arrayType->getPrefixItems()[$i];
                    }
                    $values[] = $v->convert($targetType)->value;
                }
                break;
            default:
                if ($arrayType->hasItems()) {
                    $targetType = $arrayType->getItems();
                } elseif ($arrayType->hasPrefixItems()) {
                    $targetType = $arrayType->getPrefixItems();
                } else {
                    $targetType = $this->type;
                }
                $values[] = $this->convert($targetType)->value;
                break;
        }
        return $values;
    }

    private function toString(DataType $toStringType): string
    {
        $format   = $toStringType->getFormat();
        $locale   = $toStringType->getLocale();
        $timeZone = $toStringType->getTimeZone();
        $str = '';

        switch($this->type->type) {
            case JsonSchemaTypes::String:

                if($this->type->expectsFormat(JsonSchemaFormats::Date)) {
                    $f = new IntlDateFormatter(
                        $locale,
                        IntlDateFormatter::SHORT,
                        IntlDateFormatter::NONE,
                        $timeZone,
                        IntlDateFormatter::GREGORIAN
                    );
                    $str = $f->format(new DateTime($this->value));
                }
                elseif($this->type->expectsFormat(JsonSchemaFormats::DateTime)) {
                    $f = new IntlDateFormatter(
                        $locale,
                        IntlDateFormatter::SHORT,
                        IntlDateFormatter::SHORT,
                        $timeZone,
                        IntlDateFormatter::GREGORIAN
                    );
                    $str = $f->format(new DateTime($this->value));
                }
                elseif($this->type->expectsFormat(JsonSchemaFormats::Time)) {
                    throw new NotImplemented('time to string conversion');
                }
                else {
                    $str = $this->value;
                }

                break;
            case JsonSchemaTypes::Number:
                $f = new NumberFormatter($locale, NumberFormatter::DECIMAL);
                $str = $f->format($this->value);
                break;
            case JsonSchemaTypes::Boolean:
                $str = $this->value?'1':'0';
                break;
            case JsonSchemaTypes::Array:
                $values = [];
                for($i=0; $i < count($this->value); $i++) {
                    if($this->type->hasItems()){
                        $sourceType = $this->type->getItems();
                    } else {
                        $sourceType = $this->type->getPrefixItems()[$i];
                    }
                    $v = new TypedValue($this->value[$i], $sourceType);
                    $values[] = $v->toString($toStringType);
                }
                // remove empty values
                $values = array_filter($values, fn($value) => $value !== '');

                $str = implode($toStringType->getValueSeparator(), $values);
                break;
            default:
                $str = (string) $this->value;
        }

        if($toStringType->expectsFormat(JsonSchemaFormats::PlainText)) {
            $str = $this->toPlainText($str);
        }

        // Enforce max length schema for string
        if($toStringType->getMaxLength() > 0) {
            $str = substr($str,0, $toStringType->getMaxLength());
        }

        return $str;
    }

    private function coerceLocalizedStringToDate($value, $locale, $timeZone)
    {
        $f = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $timeZone,
            IntlDateFormatter::GREGORIAN
        );
        $f->setLenient(false);
        $f->setPattern("y-MM-dd");
        $v = $f->parse($value);
        if (! $v) {
            $f = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::SHORT,
                IntlDateFormatter::NONE,
                $timeZone,
                IntlDateFormatter::GREGORIAN
            );
            $v = $f->parse($value);
        }
        if ($v) {
            $d = new DateTime();
            return $d->setTimestamp($v)->format("Y-m-d");
        }
    }

    private function coerceLocalizedStringToDateTime($value, $locale, $timeZone): string
    {
        $f = new IntlDateFormatter(
            $locale,
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $timeZone,
            IntlDateFormatter::GREGORIAN
        );
        $f->setLenient(false);
        $f->setPattern("yyyy-MM-dd'T'HH:mm:ss.SSSXXX");
        $v = $f->parse($value);

        if(!$v) {
            $f = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $timeZone,
                IntlDateFormatter::GREGORIAN
            );
            $f->setPattern("yyyy-MM-dd'T'HH:mm:ssXXX");
            $v = $f->parse($value);
        }

        if(!$v) {
            $f = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $timeZone,
                IntlDateFormatter::GREGORIAN
            );
            $f->setLenient(false);
            $f->setPattern("yyyy-MM-dd HH:mm");
            $v = $f->parse($value);
        }

        if(!$v) {

            $f = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::SHORT,
                IntlDateFormatter::SHORT,
                $timeZone,
                IntlDateFormatter::GREGORIAN
            );
            $v = $f->parse($value);
        }
        if(!$v) {
            // Match time without minutes.
            $f->setPattern("M/d/y ha");
            $v = $f->parse($value);
        }
        if(!$v) {
            // Match time with seconds
            $f = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::SHORT,
                IntlDateFormatter::MEDIUM,
                $timeZone,
                IntlDateFormatter::GREGORIAN
            );
            $v = $f->parse($value);
        }
        if(!$v) {
            throw new DataConversionException(sprintf("Cannot convert '%s' to datetime in %s locale.", $value, $locale));
        }
        $d = new DateTime();
        return $d->setTimestamp($v)->format(DateTimeInterface::ISO8601);
    }

    /**
     * Removes HTML markup, transforms BR to line break;
     * @param string $str
     *
     * @return string
     */
    private function toPlainText(string $str): string
    {
        $str = strip_tags($str,'<br>');
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $str);
    }


}
