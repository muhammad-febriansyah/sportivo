import { useFlashToast } from '@/hooks/use-flash-toast';
import { Toaster as Sonner, type ToasterProps } from 'sonner';

function Toaster({ ...props }: ToasterProps) {
    useFlashToast();

    return (
        <Sonner
            theme="light"
            richColors
            className="toaster group"
            position="top-right"
            {...props}
        />
    );
}

export { Toaster };
