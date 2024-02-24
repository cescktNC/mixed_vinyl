<?php

namespace App\Service;

use Psr\Cache\CacheItemInterface;
use Symfony\Bridge\Twig\Command\DebugCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MixRepository
{
    // 1ª Manera de Inyección de dependencias
//    private HttpClientInterface $httpClient;
//    private CacheInterface $cache;
//    public function __construct(
//        HttpClientInterface $httpClient,
//        CacheInterface $cache
//    )
//    {
//        $this->httpClient = $httpClient;
//        $this->cache = $cache;
//    }

    // 2ª Manera de Inyección de dependencias
    public function __construct(
        private HttpClientInterface $githubContentClient,
        private CacheInterface $cache,
        #[Autowire('%kernel.debug%')]
        private bool $isDebug,
        #[Autowire(service: 'twig.command.debug')]
        private DebugCommand $twigDebugCommand
    )
    {
    }

    public function findAll(): array
    {
        // Esto funciona así:
        // Las peticiones http són más lentas, entonces para ganar velocidad se usa la caché.
        // Pero la primera vez que se entra en la web, la caché está vacía, y hay que llenarla con los datos, que en
        // este caso son las canciones. El funcionamiento será, que la primera vez se hara una petición http a la
        // API para recopilar las canciones y posteriormente se guardaran en cache para que las futuras consultas
        // se hagan en cache y no en http. Des esta forma se gana más velocidad.
        // El método get lo que hace es ir a buscar las canciones en la variable clave 'mixes_data' y si no existe
        // dicha variable, lanza la función que se pasa como segundo parámetros, que es la petición http.
        // A esta funcion, se le pasa una variable de tipo 'CacheItemInterface' para especificarle el tiempo en segundos
        // que tardará en expirarse las canciones en caché
        return $this->cache->get('mixes_data', function (CacheItemInterface $cacheItem) {
            $cacheItem->expiresAfter($this->isDebug ? 5 : 60);
            $response = $this->githubContentClient->request('GET', '/SymfonyCasts/vinyl-mixes/main/mixes.json');
            return $response->toArray();
        });
    }
}