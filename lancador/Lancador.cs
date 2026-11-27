// Lançador do MyGameList: liga o PHP portátil (só para este computador), abre o programa numa janela própria
// do Microsoft Edge e desliga tudo quando a janela é fechada.
// Compilar: compilar.bat (usa o compilador C# que já vem no Windows; sem instalar nada).
using System;
using System.Diagnostics;
using System.IO;
using System.Management;
using System.Net;
using System.Net.Sockets;
using System.Runtime.InteropServices;
using System.Text;
using System.Threading;
using System.Windows.Forms;
using Microsoft.Win32;

static class Lancador
{
    const string Titulo = "MyGameList";
    const int PortaPreferida = 47615;   // a mesma porta sempre: assim o navegador lembra tema e preferências

    [STAThread]
    static int Main()
    {
        string pasta = AppDomain.CurrentDomain.BaseDirectory;
        string php = Path.Combine(pasta, "php", "php.exe");
        string publico = Path.Combine(pasta, "app", "public");
        string dados = Environment.GetEnvironmentVariable("MGL_DATA");
        if (string.IsNullOrEmpty(dados))
            dados = Path.Combine(Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "MyGameList");

        try
        {
            if (!File.Exists(php) || !File.Exists(Path.Combine(publico, "router.php")))
                return Erro("Faltam arquivos do programa. Deixe o MyGameList.exe junto das pastas \"app\" e \"php\".");
            Directory.CreateDirectory(Path.Combine(dados, "logs"));
            string arquivoPorta = Path.Combine(dados, "porta.txt");
            string perfil = Path.Combine(dados, "janela");

            bool primeira;
            using (Mutex unico = new Mutex(true, "Local\\MyGameList-" + dados.ToLowerInvariant().GetHashCode(), out primeira))
            {
                if (!primeira)
                {
                    // já está aberto: só abre outra janela no mesmo servidor
                    int atual;
                    for (int i = 0; i < 40; i++)
                    {
                        if (File.Exists(arquivoPorta) && int.TryParse(File.ReadAllText(arquivoPorta).Trim(), out atual) && Responde(atual))
                        {
                            AbrirJanela(Endereco(atual), perfil);
                            return 0;
                        }
                        Thread.Sleep(250);
                    }
                    return Erro("O MyGameList parece estar aberto, mas não respondeu. Feche-o pelo Gerenciador de Tarefas e abra de novo.");
                }

                int porta = PortaLivre(PortaPreferida) ? PortaPreferida : PortaQualquer();
                using (Process servidor = IniciarServidor(php, publico, dados, porta))
                {
                    try
                    {
                        PrenderAoLancador(servidor);   // se o lançador for encerrado, o PHP vai junto
                        bool pronto = false;
                        for (int i = 0; i < 80 && !pronto && !servidor.HasExited; i++)
                        {
                            pronto = Responde(porta);
                            if (!pronto) Thread.Sleep(200);
                        }
                        if (!pronto)
                            return Erro("O programa não conseguiu iniciar. Veja o arquivo:\n" + Path.Combine(dados, "logs", "servidor.log"));
                        File.WriteAllText(arquivoPorta, porta.ToString());

                        Process janela = AbrirJanela(Endereco(porta), perfil);
                        if (janela == null)
                        {
                            // sem o Edge: usa o navegador padrão e mantém o servidor ligado até o aviso ser fechado
                            Process.Start(Endereco(porta));
                            MessageBox.Show("O MyGameList foi aberto no seu navegador.\n\nDeixe este aviso aberto enquanto usar e clique em OK para encerrar.", Titulo);
                        }
                        else
                        {
                            janela.WaitForExit();
                            // o Edge pode passar a janela para outro processo: espera todos os que usam o nosso perfil
                            Thread.Sleep(800);
                            while (JanelaAberta(perfil)) Thread.Sleep(1000);
                        }
                    }
                    finally
                    {
                        try { if (!servidor.HasExited) servidor.Kill(); } catch (Exception) { }
                        try { File.Delete(arquivoPorta); } catch (Exception) { }
                    }
                }
            }
            return 0;
        }
        catch (Exception e)
        {
            return Erro("Não foi possível abrir o MyGameList.\n\n" + e.Message);
        }
    }

    static int Erro(string texto)
    {
        if (Environment.GetEnvironmentVariable("MGL_SEM_AVISOS") != "1")
            MessageBox.Show(texto, Titulo, MessageBoxButtons.OK, MessageBoxIcon.Error);
        return 1;
    }

    static string Endereco(int porta) { return "http://127.0.0.1:" + porta + "/"; }

    static bool PortaLivre(int porta)
    {
        TcpListener l = new TcpListener(IPAddress.Loopback, porta);
        try { l.Start(); l.Stop(); return true; }
        catch (SocketException) { return false; }
    }

    static int PortaQualquer()
    {
        TcpListener l = new TcpListener(IPAddress.Loopback, 0);
        l.Start();
        int porta = ((IPEndPoint)l.LocalEndpoint).Port;
        l.Stop();
        return porta;
    }

    static Process IniciarServidor(string php, string publico, string dados, int porta)
    {
        string pastaPhp = Path.GetDirectoryName(php);
        ProcessStartInfo i = new ProcessStartInfo(php);
        i.Arguments = "-c \"" + Path.Combine(pastaPhp, "php.ini") + "\" -d extension_dir=\"" + Path.Combine(pastaPhp, "ext") + "\""
            + " -S 127.0.0.1:" + porta + " -t \"" + publico + "\" \"" + Path.Combine(publico, "router.php") + "\"";
        i.WorkingDirectory = pastaPhp;
        i.UseShellExecute = false;
        i.CreateNoWindow = true;
        i.RedirectStandardError = true;
        i.RedirectStandardOutput = true;
        i.EnvironmentVariables["MGL_DATA"] = dados;
        i.EnvironmentVariables.Remove("PHPRC");

        string log = Path.Combine(dados, "logs", "servidor.log");
        try { File.Delete(log); } catch (Exception) { }
        Process p = new Process();
        p.StartInfo = i;
        object trava = new object();
        // o PHP escreve uma linha por acesso: precisa ser lido sempre; só os problemas são guardados
        DataReceivedEventHandler ler = delegate (object s, DataReceivedEventArgs e)
        {
            if (e.Data == null) return;
            if (e.Data.EndsWith(" Accepted") || e.Data.EndsWith(" Closing") || e.Data.Contains("[200]:") || e.Data.Contains("[201]:") || e.Data.Contains("[204]:") || e.Data.Contains("[302]:")) return;
            lock (trava) { try { File.AppendAllText(log, e.Data + Environment.NewLine, Encoding.UTF8); } catch (Exception) { } }
        };
        p.ErrorDataReceived += ler;
        p.OutputDataReceived += ler;
        p.Start();
        p.BeginErrorReadLine();
        p.BeginOutputReadLine();
        return p;
    }

    static bool Responde(int porta)
    {
        try
        {
            HttpWebRequest r = (HttpWebRequest)WebRequest.Create(Endereco(porta) + "__mygamelist");
            r.Timeout = 1500;
            r.Proxy = null;
            using (WebResponse resposta = r.GetResponse())
            using (StreamReader leitor = new StreamReader(resposta.GetResponseStream()))
                return leitor.ReadToEnd() == "MyGameList";
        }
        catch (Exception) { return false; }
    }

    static string AcharEdge()
    {
        foreach (RegistryKey raiz in new RegistryKey[] { Registry.CurrentUser, Registry.LocalMachine })
        {
            using (RegistryKey k = raiz.OpenSubKey("SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\App Paths\\msedge.exe"))
            {
                string v = k == null ? null : k.GetValue("") as string;
                if (v != null && File.Exists(v)) return v;
            }
        }
        foreach (Environment.SpecialFolder f in new Environment.SpecialFolder[] { Environment.SpecialFolder.ProgramFilesX86, Environment.SpecialFolder.ProgramFiles })
        {
            string v = Path.Combine(Environment.GetFolderPath(f), "Microsoft", "Edge", "Application", "msedge.exe");
            if (File.Exists(v)) return v;
        }
        return null;
    }

    static Process AbrirJanela(string endereco, string perfil)
    {
        string edge = AcharEdge();
        if (edge == null) return null;
        string extra = Environment.GetEnvironmentVariable("MGL_EDGE_EXTRA") ?? "";
        ProcessStartInfo i = new ProcessStartInfo(edge,
            "--app=" + endereco + " --user-data-dir=\"" + perfil + "\" --no-first-run --no-default-browser-check --disable-background-mode --disable-sync --window-size=1366,860 " + extra);
        i.UseShellExecute = false;
        return Process.Start(i);
    }

    static bool JanelaAberta(string perfil)
    {
        try
        {
            using (ManagementObjectSearcher busca = new ManagementObjectSearcher("SELECT CommandLine FROM Win32_Process WHERE Name = 'msedge.exe'"))
                foreach (ManagementBaseObject o in busca.Get())
                {
                    string linha = o["CommandLine"] as string;
                    if (linha != null && linha.IndexOf(perfil, StringComparison.OrdinalIgnoreCase) >= 0) return true;
                }
        }
        catch (Exception) { }
        return false;
    }

    // ---- "Job" do Windows: processos presos a ele terminam junto com o lançador ----
    [StructLayout(LayoutKind.Sequential)] struct LimiteBasico { public long a, b; public uint Flags; public UIntPtr c, d; public uint e; public UIntPtr f; public uint g, h; }
    [StructLayout(LayoutKind.Sequential)] struct Contadores { public ulong a, b, c, d, e, f; }
    [StructLayout(LayoutKind.Sequential)] struct LimiteEstendido { public LimiteBasico Basico; public Contadores Io; public UIntPtr a, b, c, d; }
    [DllImport("kernel32.dll", CharSet = CharSet.Unicode)] static extern IntPtr CreateJobObject(IntPtr atributos, string nome);
    [DllImport("kernel32.dll")] static extern bool SetInformationJobObject(IntPtr job, int classe, ref LimiteEstendido info, uint tamanho);
    [DllImport("kernel32.dll")] static extern bool AssignProcessToJobObject(IntPtr job, IntPtr processo);
    static IntPtr job;

    static void PrenderAoLancador(Process p)
    {
        job = CreateJobObject(IntPtr.Zero, null);
        LimiteEstendido info = new LimiteEstendido();
        info.Basico.Flags = 0x2000;   // JOB_OBJECT_LIMIT_KILL_ON_JOB_CLOSE
        SetInformationJobObject(job, 9, ref info, (uint)Marshal.SizeOf(typeof(LimiteEstendido)));
        AssignProcessToJobObject(job, p.Handle);
    }
}
