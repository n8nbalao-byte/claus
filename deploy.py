import ftplib
import os
import ssl

# Configurações do FTP
FTP_HOST = "147.93.37.68"
FTP_USER = "u770915504.n8nbalao.com"
FTP_PASS = "Aa366560402@"

def upload_files():
    print(f"Tentando conectar ao FTP {FTP_HOST} com usuário {FTP_USER}...")
    
    # Tentar conexão segura (FTPS) primeiro, pois é padrão hoje em dia
    try:
        # Contexto SSL para ignorar erros de certificado se necessário (comum em hospedagem compartilhada)
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
        
        ftp = ftplib.FTP_TLS(context=ctx)
        ftp.connect(FTP_HOST)
        ftp.login(FTP_USER, FTP_PASS)
        ftp.prot_p() # Forçar proteção de dados
        print("Conexão FTPS (Segura) estabelecida!")
    except Exception as e_tls:
        print(f"Falha ao conectar via FTPS: {e_tls}")
        print("Tentando conexão FTP padrão (insegura)...")
        try:
            ftp = ftplib.FTP(FTP_HOST)
            ftp.login(FTP_USER, FTP_PASS)
            print("Conexão FTP (Padrão) estabelecida!")
        except Exception as e:
            print(f"Erro fatal ao conectar: {e}")
            return

    # Navegação
    try:
        # Tenta listar para ver onde estamos
        print("Diretório atual:", ftp.pwd())
        
        # Tenta entrar em public_html (se já não estiver lá)
        try:
            ftp.cwd("public_html")
            print("Entrou em public_html")
        except:
            print("Não foi possível entrar em public_html (talvez já esteja na raiz correta ou não exista).")

        # Código anterior que criava admin-claus removido para fazer deploy na raiz
        # Agora os arquivos ficarão direto em public_html (n8nbalao.com)
        
    except Exception as e:
        print(f"Erro na navegação de diretórios: {e}")
        ftp.quit()
        return

    # Upload
    files_to_upload = ['index.php', 'api.php', 'db.php', 'webhook.php', 'README.txt', 'view_logs.php', 'update_db_ai.sql', 'update_db_groq.sql', 'update_groq_model.sql']
    
    print(f"Iniciando upload de {len(files_to_upload)} arquivos...")

    for filename in files_to_upload:
        try:
            with open(filename, "rb") as file:
                print(f"Enviando {filename}...")
                ftp.storbinary(f"STOR {filename}", file)
                print(f"-> OK")
        except Exception as e:
            print(f"Erro ao enviar {filename}: {e}")

    ftp.quit()
    print("Deploy concluído com sucesso!")

if __name__ == "__main__":
    upload_files()
