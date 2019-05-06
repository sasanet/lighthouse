<?php

namespace Nuwave\Lighthouse\Schema;

use function Functional\map;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use Nuwave\Lighthouse\Schema\AST\DocumentAST;
use Nuwave\Lighthouse\Schema\Factories\ClientDirectiveFactory;

class SchemaBuilder
{
    /**
     * @var \Nuwave\Lighthouse\Schema\TypeRegistry
     */
    protected $typeRegistry;

    /**
     * @var \Nuwave\Lighthouse\Schema\Factories\ClientDirectiveFactory
     */
    protected $clientDirectiveFactory;

    /**
     * @param  \Nuwave\Lighthouse\Schema\TypeRegistry  $typeRegistry
     * @param  \Nuwave\Lighthouse\Schema\Factories\ClientDirectiveFactory  $clientDirectiveFactory
     * @return void
     */
    public function __construct(
        TypeRegistry $typeRegistry,
        ClientDirectiveFactory $clientDirectiveFactory
    ) {
        $this->typeRegistry = $typeRegistry;
        $this->clientDirectiveFactory = $clientDirectiveFactory;
    }

    /**
     * Build an executable schema from AST.
     *
     * @param  \Nuwave\Lighthouse\Schema\AST\DocumentAST  $documentAST
     * @return \GraphQL\Type\Schema
     */
    public function build(DocumentAST $documentAST)
    {
        $config = SchemaConfig::create();

        $this->typeRegistry->setDocumentAST($documentAST);

        // Always set Query since it is required
        $config->setQuery(
            $this->typeRegistry->get('Query')
        );

        $config->setDirectives(
            map(
                $documentAST->directives,
                [$this->clientDirectiveFactory, 'handle']
            )
        );

        // Those are optional so only add them if they are present in the schema
        if (isset($documentAST->types['Mutation'])) {
            $config->setMutation(
                $this->typeRegistry->get('Mutation')
            );
        }
        if (isset($documentAST->types['Subscription'])) {
            $config->setSubscription(
                $this->typeRegistry->get('Subscription')
            );
        }

        $config->setTypeLoader(
            [$this->typeRegistry, 'get']
        );

        return new Schema($config);
    }
}
