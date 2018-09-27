<?php
namespace Icecave\Flax;

interface HessianClientInterface
{
    /**
     * Invoke a Hessian operation.
     *
     * @param string       $name      The name of the operation to invoke.
     * @param array<mixed> $arguments Arguments to the operation.
     */
    public function __call($name, array $arguments);

    /**
     * Invoke a Hessian operation.
     *
     * @param string $name          The name of the operation to invoke.
     * @param mixed  $arguments,... Arguments to the operation.
     *
     * @return mixed                                   The result of the Hessian call.
     * @throws Exception\AbstractHessianFaultException
     */
    public function invoke($name);

    /**
     * Invoke a Hessian operation.
     *
     * @param string       $name      The name of the operation to invoke.
     * @param array<mixed> $arguments Arguments to the operation.
     *
     * @return mixed                                   The result of the Hessian call.
     * @throws Exception\AbstractHessianFaultException
     */
    public function invokeArray($name, array $arguments = []);
}
