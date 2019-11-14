<?php

namespace Hyperf\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use TheCodingMachine\GraphQLite\GraphQLException;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use TheCodingMachine\GraphQLite\Types\ArgumentResolver;

class QueryField extends FieldDefinition
{
    /**
     * QueryField constructor.
     * @param string $name
     * @param OutputType&Type $type
     * @param array[] $arguments Indexed by argument name, value: ['type'=>InputType, 'defaultValue'=>val].
     * @param callable|null $resolve The method to execute
     * @param string|null $targetMethodOnSource The name of the method to execute on the source object. Mutually exclusive with $resolve parameter.
     * @param ArgumentResolver $argumentResolver
     * @param null|string $comment
     * @param bool $injectSource Whether to inject the source object (for Fields), or null for Query and Mutations
     * @param array $additionalConfig
     */
    public function __construct(string $name, OutputType $type, array $arguments, ?callable $resolve, ?string $targetMethodOnSource, ArgumentResolver $argumentResolver, ?string $comment, bool $injectSource, array $additionalConfig = [])
    {
        $config = [
            'name' => $name,
            'type' => $type,
            'args' => array_map(function(array $item) { return $item['type']; }, $arguments)
        ];
        if ($comment) {
            $config['description'] = $comment;
        }

        /**
         * 这里完善了 graphql resolve 方法的完整参数
         * author peer
         * 2019-11-14
         */
        $config['resolve'] = function ($source, array $args, $context, ResolveInfo $info) use ($resolve, $targetMethodOnSource, $arguments, $injectSource, $argumentResolver) {
            $toPassArgs = [];
            if ($injectSource) {
                $toPassArgs[] = $source;
            }
            foreach ($arguments as $name => $arr) {
                $type = $arr['type'];
                if (isset($args[$name])) {
                    $val = $argumentResolver->resolve($args[$name], $type);
                } elseif (array_key_exists('defaultValue', $arr)) {
                    $val = $arr['defaultValue'];
                } else {
                    throw new GraphQLException("Expected argument '$name' was not provided.");
                }

                $toPassArgs[] = $val;
            }

            if ($resolve !== null) {
                /**
                 * 这里将 ResolveInfo $info 加入了 resolve 函数回调
                 * author peer
                 * 2019-11-14
                 */
                array_unshift($toPassArgs, $info);
                return $resolve(...$toPassArgs);
            }
            if ($targetMethodOnSource !== null) {
                $method = [$source, $targetMethodOnSource];
                return $method(...$toPassArgs);
            }
            throw new InvalidArgumentException('The QueryField constructor should be passed either a resolve method or a target method on source object.');
        };

        $config += $additionalConfig;
        parent::__construct($config);
    }
}