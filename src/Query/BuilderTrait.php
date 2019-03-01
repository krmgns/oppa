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
    public function equal(...$arguments): self
    {
        return $this->whereEqual(...$arguments);
    }

    /**
     * Not equal.
     * @aliasOf whereNotEqual()
     */
    public function notEqual(...$arguments): self
    {
        return $this->whereNotEqual(...$arguments);
    }

    /**
     * Null.
     * @aliasOf whereNull()
     */
    public function null(...$arguments): self
    {
        return $this->whereNull(...$arguments);
    }

    /**
     * Not nuull.
     * @aliasOf whereNotNull()
     */
    public function notNull(...$arguments): self
    {
        return $this->whereNotNull(...$arguments);
    }

    /**
     * In.
     * @aliasOf whereIn()
     */
    public function in(...$arguments): self
    {
        return $this->whereIn(...$arguments);
    }

    /**
     * Not in.
     * @aliasOf whereNotIn()
     */
    public function notIn(...$arguments): self
    {
        return $this->whereNotIn(...$arguments);
    }

    /**
     * Between.
     * @aliasOf whereBetween()
     */
    public function between(...$arguments): self
    {
        return $this->whereBetween(...$arguments);
    }

    /**
     * Not between.
     * @aliasOf whereNotBetween()
     */
    public function notBetween(...$arguments): self
    {
        return $this->whereNotBetween(...$arguments);
    }

    /**
     * Less than.
     * @aliasOf whereLessThan()
     */
    public function lessThan(...$arguments): self
    {
        return $this->whereLessThan(...$arguments);
    }

    /**
     * Less than equal.
     * @aliasOf whereLessThanEqual()
     */
    public function lessThanEqual(...$arguments): self
    {
        return $this->whereLessThanEqual(...$arguments);
    }

    /**
     * Greater than.
     * @aliasOf whereGreaterThan()
     */
    public function greaterThan(...$arguments): self
    {
        return $this->whereGreaterThan(...$arguments);
    }

    /**
     * Greater than equal.
     * @aliasOf whereGreaterThanEqual()
     */
    public function greaterThanEqual(...$arguments): self
    {
        return $this->whereGreaterThanEqual(...$arguments);
    }

    /**
     * Like.
     * @aliasOf whereLike()
     */
    public function like(...$arguments): self
    {
        return $this->whereLike(...$arguments);
    }

    /**
     * Not like.
     * @aliasOf whereNotLike()
     */
    public function notLike(...$arguments): self
    {
        return $this->whereNotLike(...$arguments);
    }

    /**
     * Like start.
     * @aliasOf whereLikeStart()
     */
    public function likeStart(...$arguments): self
    {
        return $this->whereLikeStart(...$arguments);
    }

    /**
     * Like end.
     * @aliasOf whereLikeEnd()
     */
    public function likeEnd(...$arguments): self
    {
        return $this->whereLikeEnd(...$arguments);
    }

    /**
     * Like both.
     * @aliasOf whereLikeBoth()
     */
    public function likeBoth(...$arguments): self
    {
        return $this->whereLikeBoth(...$arguments);
    }

    /**
     * Exists.
     * @aliasOf whereExists()
     */
    public function exists(...$arguments): self
    {
        return $this->whereExists(...$arguments);
    }

    /**
     * Not exists.
     * @aliasOf whereNotExists()
     */
    public function notExists(...$arguments): self
    {
        return $this->whereNotExists(...$arguments);
    }

    /**
     * Group.
     * @aliasOf groupBy()
     */
    public function group(...$arguments): self
    {
        return $this->groupBy(...$arguments);
    }

    /**
     * Order.
     * @aliasOf orderBy()
     */
    public function order(...$arguments): self
    {
        return $this->orderBy(...$arguments);
    }

    /**
     * Order asc.
     * @aliasOf orderByAsc()
     */
    public function orderAsc(...$arguments): self
    {
        return $this->orderByAsc(...$arguments);
    }

    /**
     * Order desc.
     * @aliasOf orderByDesc()
     */
    public function orderDesc(...$arguments): self
    {
        return $this->orderByDesc(...$arguments);
    }

    /**
     * Asc.
     * @aliasOf orderByAsc()
     */
    public function asc(...$arguments): self
    {
        return $this->orderByAsc(...$arguments);
    }

    /**
     * Desc.
     * @aliasOf orderByDesc()
     */
    public function desc(...$arguments): self
    {
        return $this->orderByDesc(...$arguments);
    }

    /**
     * Sort.
     * @aliasOf orderBy()
     */
    public function sort(...$arguments): self
    {
        return $this->orderBy(...$arguments);
    }

    /**
     * Is.
     * @aliasOf whereEqual()
     */
    public function is(...$arguments): self
    {
        return $this->whereEqual(...$arguments);
    }

    /**
     * Is not.
     * @aliasOf whereNotEqual()
     */
    public function isNot(...$arguments): self
    {
        return $this->whereNotEqual(...$arguments);
    }

    /**
     * Is equal.
     * @aliasOf whereEqual()
     */
    public function isEqual(...$arguments): self
    {
        return $this->whereEqual(...$arguments);
    }

    /**
     * Is not equal.
     * @aliasOf whereNotEqual()
     */
    public function isNotEqual(...$arguments): self
    {
        return $this->whereNotEqual(...$arguments);
    }

    /**
     * Is null.
     * @aliasOf whereNull()
     */
    public function isNull(...$arguments): self
    {
        return $this->whereNull(...$arguments);
    }

    /**
     * Is not null.
     * @aliasOf whereNotNull()
     */
    public function isNotNull(...$arguments): self
    {
        return $this->whereNotNull(...$arguments);
    }

    /**
     * Is in.
     * @aliasOf whereIn().
     */
    public function isIn(...$arguments): self
    {
        return $this->whereIn(...$arguments);
    }

    /**
     * Is not in.
     * @aliasOf whereNotIn().
     */
    public function isNotIn(...$arguments): self
    {
        return $this->whereNotIn(...$arguments);
    }

    /**
     * Is between.
     * @aliasOf whereBetween().
     */
    public function isBetween(...$arguments): self
    {
        return $this->whereBetween(...$arguments);
    }

    /**
     * Is not between.
     * @aliasOf whereNotBetween()
     */
    public function isNotBetween(...$arguments): self
    {
        return $this->whereNotBetween(...$arguments);
    }

    /**
     * Eq.
     * @aliasOf whereEqual()
     */
    public function eq(...$arguments): self
    {
        return $this->whereEqual(...$arguments);
    }

    /**
     * Neq.
     * @aliasOf whereNotEqual()
     */
    public function neq(...$arguments): self
    {
        return $this->whereNotEqual(...$arguments);
    }

    /**
     * Lt.
     * @aliasOf whereLessThan()
     */
    public function lt(...$arguments): self
    {
        return $this->whereLessThan(...$arguments);
    }

    /**
     * Lte.
     * @aliasOf whereLessThanEqual()
     */
    public function lte(...$arguments): self
    {
        return $this->whereLessThanEqual(...$arguments);
    }

    /**
     * Gt.
     * @aliasOf whereGreaterThan()
     */
    public function gt(...$arguments): self
    {
        return $this->whereGreaterThan(...$arguments);
    }

    /**
     * Gte.
     * @aliasOf whereGreaterThanEqual()
     */
    public function gte(...$arguments): self
    {
        return $this->whereGreaterThanEqual(...$arguments);
    }

    /**
     * Esc.
     * @aliasOf agent.escape()
     */
    public function esc(...$arguments)
    {
        $this->agent->escape(...$arguments);
    }

    /**
     * Esc id.
     * @aliasOf agent.escapeIdentifier()
     */
    public function escId(...$arguments)
    {
        $this->agent->escapeIdentifier(...$arguments);
    }

    /**
     * Esc field.
     * @aliasOf agent.escapeIdentifier()
     */
    public function escField(...$arguments)
    {
        $this->agent->escapeIdentifier(...$arguments);
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
