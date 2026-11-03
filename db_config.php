<?php
// Ficheiro: db_config.php
// Objetivo: Conectar ao banco de dados usando DATABASE_URL (para produção)
//           ou credenciais locais (para desenvolvimento) e inicializar o DB (Plug and Play).

$pdo = null;

// =====================================================================
// 1. LÓGICA DE CONEXÃO: PRODUÇÃO (DATABASE_URL) vs. LOCAL
// =====================================================================

if (getenv('DATABASE_URL')) {
    // ------------------------------------------------
    // MODO PRODUÇÃO: Usando a variável de ambiente (Render/Heroku)
    // ------------------------------------------------

    // Exemplo de formato: postgres://user:password@host:port/dbname
    $db_url = getenv('DATABASE_URL');

    // Analisar a URL para extrair os componentes
    $url_components = parse_url($db_url);

    if ($url_components === false || !isset($url_components['path'])) {
        die("Erro: DATABASE_URL inválida ou ausente no ambiente de produção.");
    }

    $host = $url_components['host'];
    $port = $url_components['port'] ?? '5432';
    $dbname = ltrim($url_components['path'], '/'); // Remove a barra inicial do nome do DB
    $user = $url_components['user'];
    $password = $url_components['pass'];

    // String de Conexão (DSN) formatada para PDO (pgsql)
    // IMPORTANTE: sslmode=require é essencial para a Render
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password;sslmode=require";

} else {
    // ------------------------------------------------
    // MODO LOCAL: Usando credenciais definidas manualmente
    // ------------------------------------------------

    // Definições Locais
    $host = 'localhost';
    $port = '5432';
    $dbname = 'db-teste'; // SEU NOME DE BANCO DE DADOS LOCAL
    $user = 'postgres';
    $password = getenv('DB_PASSWORD') ?: '';

    // String de Conexão (DSN)
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";
}

// =====================================================================
// 2. CONEXÃO E INICIALIZAÇÃO
// =====================================================================

try {
    // Tenta criar a conexão PDO
    $pdo = new PDO($dsn);
    // Define o modo de erro para exceções, facilitando a depuração
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Chama a lógica de inicialização
    initializeDatabase($pdo);

} catch (PDOException $e) {
    // Se a conexão falhar, o script para.
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

/**
 * Verifica se o banco está vazio e cria as tabelas e dados iniciais (Estrutura e Seeding P&P).
 * @param PDO $pdo A conexão ativa com o banco de dados.
 */
function initializeDatabase(PDO $pdo) {
    // Verifica a existência da tabela 'produtos' (como indicador de inicialização)
    $check_table_sql = "SELECT to_regclass('public.produtos')";
    $stmt = $pdo->query($check_table_sql);
    $table_exists = $stmt->fetchColumn() !== null;

    if (!$table_exists) {
        if (php_sapi_name() !== 'cli') {
            echo "Banco de dados vazio detectado. Criando tabelas e inserindo dados iniciais...<br>";
        } else {
            echo "Banco de dados vazio detectado. Criando tabelas e inserindo dados iniciais...\n";
        }

        // --------------------------------------------------------------------------------------
        // ⚠️ USO DE HEREDOC (<<<SQL) PARA EVITAR QUEBRAS COM BARRAS INVERTIDAS NO HASH DE SENHA! ⚠️
        // --------------------------------------------------------------------------------------
        $sql_commands = <<<SQL

-- TABELA 1: admin_users
CREATE TABLE public.admin_users (
    id SERIAL PRIMARY KEY,
    username character varying(50) NOT NULL UNIQUE,
    email character varying(255) NOT NULL UNIQUE,
    password_hash character varying(255) NOT NULL,
    user_level character varying(50) DEFAULT 'Gerente' NOT NULL
);

-- TABELA 2: produtos
CREATE TABLE public.produtos (
    id SERIAL PRIMARY KEY,
    nome character varying(255) NOT NULL,
    descricao text,
    preco_atual numeric(10,2) NOT NULL,
    preco_antigo numeric(10,2),
    link_cupom character varying(100),
    imagem_principal character varying(255),
    imagens_galeria text[],
    rating numeric(2,1) DEFAULT 4.7,
    rating_count integer DEFAULT 204,
    sold_count integer DEFAULT 4473,
    tag_oferta_principal character varying(100) DEFAULT 'OFERTA REL MPAGO',
    tag_oferta_secundaria character varying(100) DEFAULT 'BLACK FRIDAY NOVEMBER',
    tag_desconto_1 character varying(100) DEFAULT 'Economize R$660,00',
    tag_desconto_2 character varying(100) DEFAULT 'Economize 82% COM CUPOM',
    shipping_info character varying(100) DEFAULT 'Receba at 15-20 de nov.',
    shipping_cost character varying(50) DEFAULT 'Gr tis',
    nome_vendedor character varying(255) DEFAULT 'EAGLE FORCE BR',
    vendas_count integer DEFAULT 117,
    url_logo_vendedor character varying(255) DEFAULT 'url_default_logo.jpg',
    descricao_completa text,
    link_checkout_externo text
);

-- TABELA 3: chat_messages
CREATE TABLE public.chat_messages (
    id SERIAL PRIMARY KEY,
    session_id character varying(255) NOT NULL,
    sender_type character varying(10) NOT NULL,
    message_content text NOT NULL,
    context_id character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);
-- Índice necessário para a sessão do chat
CREATE INDEX idx_chat_session ON public.chat_messages USING btree (session_id);

-- TABELA 4: config_gateway
CREATE TABLE public.config_gateway (
    id SERIAL PRIMARY KEY,
    chave character varying(100) NOT NULL UNIQUE,
    valor text,
    descricao character varying(255),
    data_cadastro timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);

-- TABELA 5: configuracoes_marketing
CREATE TABLE public.configuracoes_marketing (
    id SERIAL PRIMARY KEY,
    chave character varying(100) NOT NULL UNIQUE,
    valor character varying(255) NOT NULL,
    descricao character varying(255)
);

-- TABELA 6: configuracoes_site
CREATE TABLE public.configuracoes_site (
    id integer DEFAULT 1 PRIMARY KEY,
    cor_botao_comprar character varying(7) DEFAULT '#E7001A',
    cor_fundo character varying(7) DEFAULT '#FFFFFF',
    header_cor_fundo character varying(10) DEFAULT '#FFFFFF',
    header_cor_icones character varying(10) DEFAULT '#333333'
);

-- TABELA 7: pedidos
CREATE TABLE public.pedidos (
    id SERIAL PRIMARY KEY,
    user_id integer NOT NULL,
    gateway_txid character varying(255) NOT NULL,
    status character varying(50) DEFAULT 'PENDENTE' NOT NULL,
    customer_name character varying(255) NOT NULL,
    customer_email character varying(255) NOT NULL,
    customer_cpf character varying(20) NOT NULL,
    customer_phone character varying(20) NOT NULL,
    product_name character varying(255) NOT NULL,
    quantity integer NOT NULL,
    total_amount_centavos integer NOT NULL,
    pix_code text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);

-- TABELA 8: videos_criadores
CREATE TABLE public.videos_criadores (
    id SERIAL PRIMARY KEY,
    produto_id integer,
    nome_criador character varying(100) NOT NULL,
    descricao_video text,
    musica_original character varying(150),
    caminho_arquivo character varying(255) NOT NULL,
    likes integer DEFAULT 0,
    comentarios integer DEFAULT 0,
    salvos integer DEFAULT 0,
    compartilhamentos integer DEFAULT 0,
    thumbnail_url character varying(255),
    CONSTRAINT videos_criadores_produto_id_fkey FOREIGN KEY (produto_id) REFERENCES public.produtos(id)
);
-- Índice necessário para vídeos
CREATE INDEX idx_videos_criadores_produto ON public.videos_criadores USING btree (produto_id);


-- ==========================================
-- DADOS INICIAIS (INSERTs)
-- ==========================================

-- Dados na tabela admin_users (Hash de senha para 'admin' - O '$' foi escapado)
INSERT INTO public.admin_users (username, email, password_hash, user_level)
VALUES ('admin', 'admin@loja.com', '\$2y\$10\$dsJxZ8KVk5WthKH5uz1eMuTDwhhcgcQWAYSYRPJAM2TEIK4AGbt8C', 'Administrador');

-- Dados na tabela produtos (Produto de Exemplo com ID 1)
INSERT INTO public.produtos (
    id, nome, preco_atual, preco_antigo, imagem_principal, rating, nome_vendedor, url_logo_vendedor
) VALUES (
    1,
    'Cadeira Gamer Fox Office Fox Racer, RGB e Iluminação LED, até 130kgs, com almofadas, Reclinável - Preta e Branca',
    99.90,
    199.90,
    'url_default_produto.png',
    4.8,
    'EAGLE FORCE BR',
    'url_default_logo.jpeg'
) ON CONFLICT (id) DO NOTHING;

-- Garante que o próximo ID da tabela produtos seja 2 (depois de inserir o 1)
SELECT setval('public.produtos_id_seq', 2, false);

-- Dados na tabela configuracoes_site (Um registro para configurações padrão)
INSERT INTO public.configuracoes_site (id, cor_botao_comprar, cor_fundo)
VALUES (1, '#E7001A', '#FFFFFF') ON CONFLICT (id) DO NOTHING;

-- Dados na tabela videos_criadores (Assumindo que o produto_id 1 existe)
-- CORRIGIDO: 4 colunas (produto_id, nome_criador, caminho_arquivo, thumbnail_url) e 4 valores.
INSERT INTO public.videos_criadores (produto_id, nome_criador, caminho_arquivo, thumbnail_url)
VALUES (1, 'Creator_01', 'uploads/videos/videocreator.mp4', 'uploads/thumbs/thumb_creator.jpg');
SQL;
        // --------------------------------------------------------------------------------------

        // Executa o SQL.
        try {
            $pdo->exec($sql_commands);
            if (php_sapi_name() !== 'cli') {
                echo "Criação de tabelas e inserção de dados iniciais concluída com sucesso!<br>";
            } else {
                echo "Criação de tabelas e inserção de dados iniciais concluída com sucesso!\n";
            }
        } catch (PDOException $e) {
            die("Erro ao inicializar o banco de dados: " . $e->getMessage());
        }
    }

    // =========================================================================
    // BLOCO DE MIGRAÇÃO: Adicionar coluna link_checkout_externo se não existir
    // (Garante compatibilidade com bancos já existentes)
    // =========================================================================
    try {
        $pdo->exec("ALTER TABLE public.produtos ADD COLUMN IF NOT EXISTS link_checkout_externo text");
    } catch (PDOException $e) {
        error_log("Aviso (migração link_checkout_externo): " . $e->getMessage());
    }

    // Migração: Adicionar coluna review_photos (array de URLs de fotos das avaliações)
    try {
        $pdo->exec("ALTER TABLE public.produtos ADD COLUMN IF NOT EXISTS review_photos text[]");
    } catch (PDOException $e) {
        error_log("Aviso (migração review_photos): " . $e->getMessage());
    }

    // =========================================================================
    // BLOCO DE MIGRAÇÃO: Tabelas de variantes (configuração + itens)
    // =========================================================================
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS public.variantes_config (
            id integer DEFAULT 1 PRIMARY KEY,
            show_variants boolean DEFAULT true NOT NULL,
            show_cor boolean DEFAULT true NOT NULL,
            show_modelo boolean DEFAULT true NOT NULL
        )");
    } catch (PDOException $e) {
        error_log("Aviso (migração variantes_config): " . $e->getMessage());
    }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS public.variantes (
            id SERIAL PRIMARY KEY,
            tipo character varying(20) NOT NULL,
            nome character varying(100) NOT NULL,
            cor_hex character varying(7),
            ativo boolean DEFAULT true NOT NULL,
            ordem integer DEFAULT 0 NOT NULL
        )");
    } catch (PDOException $e) {
        error_log("Aviso (migração variantes): " . $e->getMessage());
    }

    // Garante um registro de configuração padrão
    try {
        $pdo->exec("INSERT INTO public.variantes_config (id, show_variants, show_cor, show_modelo)
                     VALUES (1, true, true, true)
                     ON CONFLICT (id) DO NOTHING");
    } catch (PDOException $e) {
        error_log("Aviso (seed variantes_config): " . $e->getMessage());
    }

    // Migra colunas show_cor / show_modelo caso a tabela já exista sem elas
    try {
        $pdo->exec("ALTER TABLE public.variantes_config ADD COLUMN IF NOT EXISTS show_cor boolean DEFAULT true NOT NULL");
        $pdo->exec("ALTER TABLE public.variantes_config ADD COLUMN IF NOT EXISTS show_modelo boolean DEFAULT true NOT NULL");
    } catch (PDOException $e) {
        error_log("Aviso (migração variantes_config colunas): " . $e->getMessage());
    }

    // Migra colunas cor_hex e ordem caso a tabela variantes já exista sem elas
    try {
        $pdo->exec("ALTER TABLE public.variantes ADD COLUMN IF NOT EXISTS cor_hex character varying(7)");
        $pdo->exec("ALTER TABLE public.variantes ADD COLUMN IF NOT EXISTS ordem integer DEFAULT 0 NOT NULL");
    } catch (PDOException $e) {
        error_log("Aviso (migração variantes colunas): " . $e->getMessage());
    }

    // Insere variantes padrão (cores) apenas se a tabela estiver vazia
    try {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM public.variantes");
        $count = $countStmt ? (int)$countStmt->fetchColumn() : 0;
        if ($count === 0) {
            $pdo->exec("INSERT INTO public.variantes (tipo, nome, cor_hex, ativo, ordem) VALUES
                ('cor', 'Branco', '#FFFFFF', true, 1),
                ('cor', 'Preto', '#1A1A1A', true, 2),
                ('cor', 'Vermelho', '#E02424', true, 3),
                ('modelo', 'Modelo padrão', NULL, true, 4)
            ");
        }
    } catch (PDOException $e) {
        error_log("Aviso (seed variantes): " . $e->getMessage());
    }

    // =========================================================================
    // BLOCO DE MIGRAÇÃO: Tabela de configuração de promoção/cupom
    // (resolve o problema do promo_config.json sendo apagado pela Render)
    // =========================================================================
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS public.promo_config (
            id integer DEFAULT 1 PRIMARY KEY,
            enabled boolean DEFAULT true NOT NULL,
            coupon_enabled boolean DEFAULT true NOT NULL,
            coupon_amount numeric(10,2) DEFAULT 10.00 NOT NULL,
            coupon_label character varying(60) DEFAULT 'Cupom exclusivo' NOT NULL,
            urgency_label character varying(60) DEFAULT 'Oferta relâmpago' NOT NULL,
            timer_minutes integer DEFAULT 10 NOT NULL
        )");
    } catch (PDOException $e) {
        error_log("Aviso (migração promo_config): " . $e->getMessage());
    }

    try {
        $pdo->exec("INSERT INTO public.promo_config (id, enabled, coupon_enabled, coupon_amount, coupon_label, urgency_label, timer_minutes)
                     VALUES (1, true, true, 10.00, 'Cupom exclusivo', 'Oferta relâmpago', 10)
                     ON CONFLICT (id) DO NOTHING");
    } catch (PDOException $e) {
        error_log("Aviso (seed promo_config): " . $e->getMessage());
    }

    // =========================================================================
    // BLOCO DE MIGRAÇÃO: Tabela de configuracoes do filtro (dev bypass key)
    // =========================================================================
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS public.filtro_config (
            id integer DEFAULT 1 PRIMARY KEY,
            dev_bypass_key character varying(64) DEFAULT 'kJ7nX2mP9qR4wZ8a' NOT NULL
        )");
    } catch (PDOException $e) {
        error_log("Aviso (migração filtro_config): " . $e->getMessage());
    }

    try {
        $pdo->exec("INSERT INTO public.filtro_config (id, dev_bypass_key)
                     VALUES (1, 'kJ7nX2mP9qR4wZ8a')
                     ON CONFLICT (id) DO NOTHING");
    } catch (PDOException $e) {
        error_log("Aviso (seed filtro_config): " . $e->getMessage());
    }
}