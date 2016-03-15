<?php

namespace BitWasp\Bitcoin\Networking\Serializer\Structure;

use BitWasp\Bitcoin\Networking\Structure\NetworkAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;

class NetworkAddressSerializer
{
    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getTemplate()
    {
        return (new TemplateFactory())
            ->uint64le()
            ->bytestring(16)
            ->uint16()
            ->getTemplate();
    }

    /**
     * @param string $ip
     * @return BufferInterface
     */
    private function getIpBuffer($ip)
    {
        $hex = (string)dechex(ip2long($ip));
        $hex = (strlen($hex) % 2 == 1) ? '0' . $hex : $hex;
        $hex = '00000000000000000000'.'ffff' . $hex;
        $buffer = Buffer::hex($hex);
        return $buffer;
    }

    /**
     * @param BufferInterface $ip
     * @return string
     */
    private function parseIpBuffer(BufferInterface $ip)
    {
        $end = $ip->slice(12, 4);

        return implode(
            ".",
            array_map(
                function ($int) {
                    return unpack("C", $int)[1];
                },
                str_split($end->getBinary(), 1)
            )
        );
    }

    /**
     * @param NetworkAddress $addr
     * @return BufferInterface
     */
    public function serialize(NetworkAddress $addr)
    {
        return $this->getTemplate()->write([
            $addr->getServices(),
            $this->getIpBuffer($addr->getIp()),
            $addr->getPort()
        ]);
    }

    /**
     * @param Parser $parser
     * @return NetworkAddress
     */
    public function fromParser(Parser & $parser)
    {
        list ($services, $ipBuffer, $port) = $this->getTemplate()->parse($parser);
        return new NetworkAddress(
            $services,
            $this->parseIpBuffer($ipBuffer),
            $port
        );
    }

    /**
     * @param $data
     * @return NetworkAddress
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
