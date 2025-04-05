import { NotificationProvider } from './contexts/NotificationContext';

const App = () => {
    return (
        <NotificationProvider>
            {/* Other providers */}
            <Router>
                <Navbar />
                {/* ... */}
            </Router>
        </NotificationProvider>
    );
}; 