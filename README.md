# 🏗️ Roni\_CmsHreflang

## ✨ Descrição

O módulo **Roni\_CmsHreflang** foi desenvolvido para gerar automaticamente tags `<link rel="alternate" hreflang="...">` em páginas CMS do Magento 2, otimizando SEO internacional e assegurando que motores de busca entendam corretamente as versões regionais de uma mesma página.

🔗 **Problema Resolvido:**\
Por padrão, o Magento 2 não gera tags hreflang para páginas CMS. Isso prejudica lojas que operam em múltiplos idiomas ou regiões, impactando negativamente no SEO.

🚀 **Solução:**\
O módulo identifica a página CMS atual, verifica quais store views estão associadas a ela e gera, de forma dinâmica, as tags hreflang correspondentes.

---

## 📦 Estrutura e Arquitetura

- **Namespace:** `Roni\CmsHreflang`
- **Padrão seguido:** PSR-12, SOLID, Magento Coding Standard
- **Design Pattern:** Block Component, Dependency Injection, Fail-safe Logging

---

## 🔥 Features

- ✔️ Geração automática de tags hreflang para páginas CMS.
- ✔️ Suporte nativo a store views ilimitadas.
- ✔️ Detecta configurações de URL (`use_store_code_in_url`).
- ✔️ Considera configurações regionais (`general/locale/code`).
- ✔️ Exclui páginas desativadas automaticamente.
- ✔️ Loga erros sem quebrar a renderização da página.

---

## 🔧 Como Funciona

### ✅ Fluxo de Execução:

1. Verifica se a página atual é uma página CMS (`cms_page_view`).
2. Obtém o **identifier** da página via bloco `cms_page`.
3. Verifica se a página está ativa no store atual.
4. Recupera todas as store views associadas a essa página (incluindo `All Store Views`).
5. Para cada store:
   - Valida se a página está ativa naquela store.
   - Busca o locale (`pt_BR`, `en_US`), convertendo para o formato hreflang (`pt-br`).
   - Verifica se o store code faz parte da URL (`web/url/use_store`).
   - Monta a URL final da página para aquela store.
   - Gera a tag hreflang no formato:

```html
<link rel="alternate" hreflang="pt-BR" href="https://example.com/pt_br/loja-sobre-nos"/>
```

6. Renderiza as tags no head da página.

---

## 🛠️ Tecnologias e Boas Práticas Aplicadas

| Item                          | Estratégia/Decisão                                    |
| ----------------------------- | ----------------------------------------------------- |
| ✔️ Dependency Injection       | Redução de acoplamento, melhor testabilidade.         |
| ✔️ Fail-safe Error Handling   | Nenhum erro quebra o site, tudo é registrado via log. |
| ✔️ PSR-12 + Magento Standards | Código limpo, legível e aderente às boas práticas.    |
| ✔️ Single Responsibility      | Cada método possui uma responsabilidade clara.        |
| ✔️ Clean Code                 | Nomes semânticos, funções pequenas e bem definidas.   |
| ✔️ Banco via Resource Model   | Consulta otimizada na `cms_page_store`.               |
| ✔️ Config Awareness           | Sensível às configs de URL e locale de cada store.    |

---

## 🏟️ Estrutura Técnica Principal

- **Block:** `Roni\CmsHreflang\Block\Hreflang`\
  👉 Responsável por todo o processamento e geração das tags.

---

## 🏦 Decisões Arquiteturais

- **Por que não usar diretamente PageRepository?**\
  O método `getById()` do `PageRepositoryInterface` no Magento é limitado quando usado fora do contexto da store atual. Por isso, utilizei o `CollectionFactory` para garantir que a verificação de status (ativa ou não) seja feita corretamente por store.

- **Por que consultar diretamente a tabela **`cms_page_store`**?**\
  O repositório não oferece uma API eficiente para mapear stores de uma página. A consulta direta torna o processo mais performático e confiável.

- **Por que Logger em todos os pontos críticos?**\
  Para garantir que erros de configuração, problemas de dados ou exceções não afetem a renderização da página, tudo é capturado e reportado nos logs do Magento.

---

## 🏠 Instalação

Via composer local:

```bash
composer require roni/module-cms-hreflang
```

Ou manualmente:

1. Copie o módulo para `app/code/Roni/CmsHreflang`.
2. Execute:

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## ⚙️ Configuração

Não é necessária nenhuma configuração manual. O módulo funciona automaticamente nas páginas CMS que possuem:

- Página ativa no store view
- URL amigável configurada corretamente

---

## 🧠 Exemplos de Uso

### Página CMS "sobre-nos" em 3 store views:

| Store View | Locale | URL                                                                          |
|------------|--------|------------------------------------------------------------------------------|
| pt\_br     | pt-BR  | [https://example.com/pt_br/sobre-nos](https://example.com/deafult/sobre-nos) |
| en\_us     | en-US  | [https://example.com/en_us/sobre-nos](https://example.com/en_us/sobre-nos)   |
| en\_gb     | en-GB  | [https://example.com/en_gb/sobre-nos](https://example.com/en_gb/sobre-nos)   |

**Tags geradas:**

```html
<link rel="alternate" hreflang="pt-BR" href="https://example.com/pt_br/sobre-nos"/>
<link rel="alternate" hreflang="en-US" href="https://example.com/en_US/sobre-nos"/>
<link rel="alternate" hreflang="es-GB" href="https://example.com/es_GB/sobre-nos"/>
```

---

## 👨‍💼 Autor

**Roni Clei J Santos**\
📧 [roneclay@gmail.com](mailto\:roneclay@gmail.com)\
🔗 [LinkedIn](https://www.linkedin.com/in/roni-clei-santos/) | [GitHub](https://github.com/roneclay)

---

## 📝 Licença

[MIT License](https://opensource.org/licenses/MIT)

