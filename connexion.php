<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Imprixo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap');
        * { font-family: 'Roboto', -apple-system, BlinkMacSystemFont, sans-serif; }
        .btn-primary { background: #e63946; transition: all 0.2s ease; }
        .btn-primary:hover { background: #d62839; }
        .btn-secondary { background: #2b2d42; transition: all 0.2s ease; }
        .btn-secondary:hover { background: #1a1b2e; }
        .nav-link { position: relative; }
        .nav-link:after { content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 2px; background: #e63946; transition: width 0.2s ease; }
        .nav-link:hover:after { width: 100%; }
    </style>
</head>
<body class="bg-gray-50">
    <div id="root"></div>
    <script type="text/babel">
        const { useState } = React;

        function Header() {
            return (
                <header className="bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-center justify-between h-16">
                            <div className="flex items-center">
                                <a href="/"><span className="text-2xl font-black text-gray-900">Imprixo</span><span className="text-2xl font-black text-red-600">Pro</span></a>
                                <nav className="hidden md:ml-10 md:flex md:space-x-8">
                                    <a href="/" className="nav-link text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">Accueil</a>
                                    <a href="/catalogue.html" className="nav-link text-gray-700 hover:text-gray-900 px-3 py-2 text-sm font-medium">Catalogue</a>
                                </nav>
                            </div>
                        </div>
                    </div>
                </header>
            );
        }

        function App() {
            const [mode, setMode] = useState('login'); // 'login' or 'register'
            const [loginData, setLoginData] = useState({ email: '', password: '' });
            const [registerData, setRegisterData] = useState({ prenom: '', nom: '', email: '', telephone: '', password: '' });
            const [error, setError] = useState('');
            const [loading, setLoading] = useState(false);

            const handleLogin = async (e) => {
                e.preventDefault();
                setError('');
                setLoading(true);

                try {
                    const response = await fetch('/api/auth-client.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'login', ...loginData })
                    });

                    const result = await response.json();
                    if (result.success) {
                        window.location.href = '/mon-compte.php';
                    } else {
                        setError(result.message || 'Erreur de connexion');
                    }
                } catch (err) {
                    setError('Erreur de connexion');
                } finally {
                    setLoading(false);
                }
            };

            const handleRegister = async (e) => {
                e.preventDefault();
                setError('');
                setLoading(true);

                try {
                    const response = await fetch('/api/auth-client.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'register', ...registerData })
                    });

                    const result = await response.json();
                    if (result.success) {
                        window.location.href = '/mon-compte.php';
                    } else {
                        setError(result.message || 'Erreur d\'inscription');
                    }
                } catch (err) {
                    setError('Erreur d\'inscription');
                } finally {
                    setLoading(false);
                }
            };

            return (
                <div className="min-h-screen bg-gray-50">
                    <Header />
                    <section className="py-12">
                        <div className="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
                            <h1 className="text-3xl font-black text-gray-900 mb-8 text-center">
                                {mode === 'login' ? 'Connexion' : 'Créer un compte'}
                            </h1>

                            {mode === 'login' ? (
                                <div className="bg-white rounded-lg p-8 border border-gray-200">
                                    <form onSubmit={handleLogin}>
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                            <input
                                                type="email"
                                                required
                                                value={loginData.email}
                                                onChange={(e) => setLoginData({...loginData, email: e.target.value})}
                                                className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                            />
                                        </div>
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-gray-700 mb-2">Mot de passe *</label>
                                            <input
                                                type="password"
                                                required
                                                value={loginData.password}
                                                onChange={(e) => setLoginData({...loginData, password: e.target.value})}
                                                className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                            />
                                        </div>
                                        {error && (
                                            <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded text-sm">
                                                {error}
                                            </div>
                                        )}
                                        <button
                                            type="submit"
                                            disabled={loading}
                                            className="w-full btn-primary text-white py-4 rounded font-bold text-lg mb-4"
                                        >
                                            {loading ? 'Connexion...' : 'Se connecter'}
                                        </button>
                                        <div className="text-center text-gray-600 mb-3">Pas encore de compte ?</div>
                                        <button
                                            type="button"
                                            onClick={() => { setMode('register'); setError(''); }}
                                            className="w-full px-6 py-3 border-2 border-gray-300 rounded text-gray-700 font-semibold hover:border-red-600"
                                        >
                                            Créer un compte
                                        </button>
                                    </form>
                                </div>
                            ) : (
                                <div className="bg-white rounded-lg p-8 border border-gray-200">
                                    <form onSubmit={handleRegister}>
                                        <div className="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <label className="block text-sm font-semibold text-gray-700 mb-2">Prénom *</label>
                                                <input
                                                    type="text"
                                                    required
                                                    value={registerData.prenom}
                                                    onChange={(e) => setRegisterData({...registerData, prenom: e.target.value})}
                                                    className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-semibold text-gray-700 mb-2">Nom *</label>
                                                <input
                                                    type="text"
                                                    required
                                                    value={registerData.nom}
                                                    onChange={(e) => setRegisterData({...registerData, nom: e.target.value})}
                                                    className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                                />
                                            </div>
                                        </div>
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-gray-700 mb-2">Email *</label>
                                            <input
                                                type="email"
                                                required
                                                value={registerData.email}
                                                onChange={(e) => setRegisterData({...registerData, email: e.target.value})}
                                                className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                            />
                                        </div>
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-gray-700 mb-2">Téléphone</label>
                                            <input
                                                type="tel"
                                                value={registerData.telephone}
                                                onChange={(e) => setRegisterData({...registerData, telephone: e.target.value})}
                                                className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                            />
                                        </div>
                                        <div className="mb-4">
                                            <label className="block text-sm font-semibold text-gray-700 mb-2">Mot de passe *</label>
                                            <input
                                                type="password"
                                                required
                                                minLength="6"
                                                value={registerData.password}
                                                onChange={(e) => setRegisterData({...registerData, password: e.target.value})}
                                                className="w-full px-4 py-3 border border-gray-300 rounded focus:ring-2 focus:ring-red-600 focus:border-transparent"
                                            />
                                            <div className="text-xs text-gray-500 mt-1">Minimum 6 caractères</div>
                                        </div>
                                        {error && (
                                            <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 rounded text-sm">
                                                {error}
                                            </div>
                                        )}
                                        <button
                                            type="submit"
                                            disabled={loading}
                                            className="w-full btn-primary text-white py-4 rounded font-bold text-lg mb-4"
                                        >
                                            {loading ? 'Inscription...' : 'S\'inscrire'}
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() => { setMode('login'); setError(''); }}
                                            className="w-full px-6 py-3 border-2 border-gray-300 rounded text-gray-700 font-semibold hover:border-red-600"
                                        >
                                            Retour à la connexion
                                        </button>
                                    </form>
                                </div>
                            )}
                        </div>
                    </section>
                </div>
            );
        }

        ReactDOM.render(<App />, document.getElementById('root'));
    </script>
</body>
</html>
