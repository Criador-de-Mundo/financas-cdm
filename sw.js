const CACHE_NAME = 'financas-cdm-v1';

// Lista de arquivos locais que o navegador vai salvar na memória do celular
const ASSETS_TO_CACHE = [
  '/financas-cdm/',
  '/financas-cdm/index.html',
  '/financas-cdm/manifest.json',
  '/financas-cdm/icone.png'
];

// Evento de Instalação: Baixa os arquivos e cria o cache local
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('PWA: Armazenando arquivos essenciais no cache...');
      return cache.addAll(ASSETS_TO_CACHE);
    }).then(() => {
      // Força o Service Worker atual a se tornar ativo imediatamente
      return self.skipWaiting();
    })
  );
});

// Evento de Ativação: Limpa caches antigos se você atualizar a versão do app
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('PWA: Limpando cache antigo:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => {
      // Garante que o SW controle a página imediatamente, sem precisar recarregar
      return self.clients.claim();
    })
  );
});

// Evento Fetch: Intercepta as requisições do site
self.addEventListener('fetch', (event) => {
  // Ignora completamente as requisições feitas para a pasta do backend (PHP/MySQL)
  if (event.request.url.includes('/backend/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      // Se o arquivo estiver no cache (modo offline), usa ele. 
      // Se não estiver, busca na internet normalmente.
      return cachedResponse || fetch(event.request);
    }).catch(() => {
      // Caso dê uma falha crítica de rede e o arquivo não esteja no cache
      if (event.request.mode === 'navigate') {
        return caches.match('/financas-cdm/index.html');
      }
    })
  );
});