import AppLogoIcon from './app-logo-icon';
import { motion } from 'framer-motion';
import { Activity } from 'lucide-react';

export default function AppLogo() {
    return (
        <>

            <div className="max-w-7xl mx-auto flex items-center justify-between">
                <div
                    className="flex items-center gap-2 text-xl font-bold"
                >
                    <div className="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <Activity className="h-5 w-5 text-primary-foreground" />
                    </div>
                    <span className="text-primary">Beacon</span>
                </div>
            </div>

        </>
    );
}
