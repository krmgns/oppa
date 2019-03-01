<?php
/**
 * Copyright (c) 2015 Kerem Güneş
 *
 * MIT License <https://opensource.org/licenses/mit>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
declare(strict_types=1);

namespace Oppa\Query;

/**
 * @package Oppa
 * @object  Oppa\Query\BuilderTrait
 * @author  Kerem Güneş <k-gun@mail.com>
 */
trait BuilderTrait
{
    // /**
    //  * Call alias.
    //  * @param  string|null $method
    //  * @param  array|null  $methodArgs
    //  * @return self
    //  */
    // private function callAlias(string $method = null, array $methodArgs = null)
    // {
    //     if ($method == null || $methodArgs == null) {
    //         $trace = debug_backtrace(0)[1];
    //         if ($method == null) $method = $trace['function'];
    //         if ($methodArgs == null) $methodArgs = $trace['args'];
    //     }
    //
    //     return $this->{$method}(...$methodArgs);
    // }

    /**
     * Equal.
     * @aliasOf whereEqual()
     */
    public function equal(): self
    {
        return $this->whereEqual(...func_get_args());
    }

    /**
     * Not equal.
     * @aliasOf whereNotEqual()
     */
    public function notEqual(): self
    {
        return $this->whereNotEqual(...func_get_args());
    }

    /**
     * Null.
     * @aliasOf whereNull()
     */
    public function null(): self
    {
        return $this->whereNull(...func_get_args());
    }

    /**
     * Not nuull.
     * @aliasOf whereNotNull()
     */
    public function notNull(): self
    {
        return $this->whereNotNull(...func_get_args());
    }

    /**
     * In.
     * @aliasOf whereIn()
     */
    public function in(): self
    {
        return $this->whereIn(...func_get_args());
    }

    /**
     * Not in.
     * @aliasOf whereNotIn()
     */
    public function notIn(): self
    {
        return $this->whereNotIn(...func_get_args());
    }

    /**
     * Between.
     * @aliasOf whereBetween()
     */
    public function between(): self
    {
        return $this->whereBetween(...func_get_args());
    }

    /**
     * Not between.
     * @aliasOf whereNotBetween()
     */
    public function notBetween(): self
    {
        return $this->whereNotBetween(...func_get_args());
    }

    /**
     * Less than.
     * @aliasOf whereLessThan()
     */
    public function lessThan(): self
    {
        return $this->whereLessThan(...func_get_args());
    }

    /**
     * Less than equal.
     * @aliasOf whereLessThanEqual()
     */
    public function lessThanEqual(): self
    {
        return $this->whereLessThanEqual(...func_get_args());
    }

    /**
     * Greater than.
     * @aliasOf whereGreaterThan()
     */
    public function greaterThan(): self
    {
        return $this->whereGreaterThan(...func_get_args());
    }

    /**
     * Greater than equal.
     * @aliasOf whereGreaterThanEqual()
     */
    public function greaterThanEqual(): self
    {
        return $this->whereGreaterThanEqual(...func_get_args());
    }

    /**
     * Like.
     * @aliasOf whereLike()
     */
    public function like(): self
    {
        return $this->whereLike(...func_get_args());
    }

    /**
     * Not like.
     * @aliasOf whereNotLike()
     */
    public function notLike(): self
    {
        return $this->whereNotLike(...func_get_args());
    }

    /**
     * Like start.
     * @aliasOf whereLikeStart()
     */
    public function likeStart(): self
    {
        return $this->whereLikeStart(...func_get_args());
    }

    /**
     * Like end.
     * @aliasOf whereLikeEnd()
     */
    public function likeEnd(): self
    {
        return $this->whereLikeEnd(...func_get_args());
    }

    /**
     * Like both.
     * @aliasOf whereLikeBoth()
     */
    public function likeBoth(): self
    {
        return $this->whereLikeBoth(...func_get_args());
    }

    /**
     * Exists.
     * @aliasOf whereExists()
     */
    public function exists(): self
    {
        return $this->whereExists(...func_get_args());
    }

    /**
     * Not exists.
     * @aliasOf whereNotExists()
     */
    public function notExists(): self
    {
        return $this->whereNotExists(...func_get_args());
    }

    /**
     * Group.
     * @aliasOf groupBy()
     */
    public function group(): self
    {
        return $this->groupBy(...func_get_args());
    }

    /**
     * Order.
     * @aliasOf orderBy()
     */
    public function order(): self
    {
        return $this->orderBy(...func_get_args());
    }

    /**
     * Order asc.
     * @aliasOf orderByAsc()
     */
    public function orderAsc(): self
    {
        return $this->orderByAsc(...func_get_args());
    }

    /**
     * Order desc.
     * @aliasOf orderByDesc()
     */
    public function orderDesc(): self
    {
        return $this->orderByDesc(...func_get_args());
    }

    /**
     * Is.
     * @aliasOf whereEqual()
     */
    public function is(): self
    {
        return $this->whereEqual(...func_get_args());
    }

    /**
     * Is not.
     * @aliasOf whereNotEqual()
     */
    public function isNot(): self
    {
        return $this->whereNotEqual(...func_get_args());
    }

    /**
     * Is equal.
     * @aliasOf whereEqual()
     */
    public function isEqual(): self
    {
        return $this->whereEqual(...func_get_args());
    }

    /**
     * Is not equal.
     * @aliasOf whereNotEqual()
     */
    public function isNotEqual(): self
    {
        return $this->whereNotEqual(...func_get_args());
    }

    /**
     * Is null.
     * @aliasOf whereNull()
     */
    public function isNull(): self
    {
        return $this->whereNull(...func_get_args());
    }

    /**
     * Is not null.
     * @aliasOf whereNotNull()
     */
    public function isNotNull(): self
    {
        return $this->whereNotNull(...func_get_args());
    }

    /**
     * Is in.
     * @aliasOf whereIn().
     */
    public function isIn(): self
    {
        return $this->whereIn(...func_get_args());
    }

    /**
     * Is not in.
     * @aliasOf whereNotIn().
     */
    public function isNotIn(): self
    {
        return $this->whereNotIn(...func_get_args());
    }

    /**
     * Is between.
     * @aliasOf whereBetween().
     */
    public function isBetween(): self
    {
        return $this->whereBetween(...func_get_args());
    }

    /**
     * Is not between.
     * @aliasOf whereNotBetween()
     */
    public function isNotBetween(): self
    {
        return $this->whereNotBetween(...func_get_args());
    }

    /**
     * E.
     * @aliasOf whereEqual()
     */
    public function e(): self
    {
        return $this->whereEqual(...func_get_args());
    }

    /**
     * Ne.
     * @aliasOf whereNotEqual()
     */
    public function ne(): self
    {
        return $this->whereNotEqual(...func_get_args());
    }

    /**
     * Lt.
     * @aliasOf whereLessThan()
     */
    public function lt(): self
    {
        return $this->whereLessThan(...func_get_args());
    }

    /**
     * Lte.
     * @aliasOf whereLessThanEqual()
     */
    public function lte(): self
    {
        return $this->whereLessThanEqual(...func_get_args());
    }

    /**
     * Gt.
     * @aliasOf whereGreaterThan()
     */
    public function gt(): self
    {
        return $this->whereGreaterThan(...func_get_args());
    }

    /**
     * Gte.
     * @aliasOf whereGreaterThanEqual()
     */
    public function gte(): self
    {
        return $this->whereGreaterThanEqual(...func_get_args());
    }

    /**
     * Id.
     * @aliasOf identifier()
     */
    public function id(string $name): Identifier
    {
        return $this->identifier($name);
    }

    /**
     * Field.
     * @aliasOf identifier()
     */
    public function field(string $name): Identifier
    {
        return $this->identifier($name);
    }

    /**
     * Identifier.
     * @param  string $name
     * @return Oppa\Query\Identifier
     */
    public function identifier(string $name): Identifier
    {
        return new Identifier($name);
    }

    /**
     * Sql.
     * @param  string $content
     * @return Oppa\Query\Sql
     */
    public function sql(string $content): Sql
    {
        return new Sql($content);
    }
}
