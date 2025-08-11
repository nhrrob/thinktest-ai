import { Head } from '@inertiajs/react';
import { useToast } from '@/hooks/use-toast';
import { useConfirmationDialog } from '@/components/confirmation-dialog';
import AppLayout from '@/layouts/app-layout';

export default function ModalToastTest() {
    const { error, success } = useToast();
    const { openDialog, closeDialog, ConfirmationDialog } = useConfirmationDialog();

    const testModalSequence = () => {
        console.log('🧪 Starting modal sequence test...');
        
        // First modal
        setTimeout(() => {
            console.log('📝 Opening first modal...');
            openDialog({
                title: "Delete First Item",
                description: "Are you sure you want to delete the first item?",
                confirmText: "Delete",
                cancelText: "Cancel",
                variant: "destructive",
                onConfirm: () => {
                    console.log('✅ First modal confirmed');
                    error('Cannot delete first item - it is assigned to users.');
                    closeDialog();
                },
            });
        }, 500);

        // Second modal (after first is closed)
        setTimeout(() => {
            console.log('📝 Opening second modal...');
            openDialog({
                title: "Delete Second Item", 
                description: "Are you sure you want to delete the second item?",
                confirmText: "Delete",
                cancelText: "Cancel",
                variant: "destructive",
                onConfirm: () => {
                    console.log('✅ Second modal confirmed');
                    error('Cannot delete second item - it is assigned to users.');
                    closeDialog();
                },
            });
        }, 3000);
    };

    const testManualSequence = (itemNumber: number) => {
        console.log(`🧪 Opening modal for item ${itemNumber}...`);
        
        openDialog({
            title: `Delete Item ${itemNumber}`,
            description: `Are you sure you want to delete item ${itemNumber}?`,
            confirmText: "Delete",
            cancelText: "Cancel", 
            variant: "destructive",
            onConfirm: () => {
                console.log(`✅ Item ${itemNumber} modal confirmed`);
                error(`Cannot delete item ${itemNumber} - it is assigned to users.`);
                closeDialog();
            },
        });
    };

    const testSuccessToast = () => {
        success('This is a success toast to verify toast system works');
    };

    return (
        <AppLayout>
            <Head title="Modal Toast Test" />
            
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 className="text-2xl font-bold mb-6">Modal Toast Integration Test</h1>
                            
                            <div className="space-y-6">
                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Automated Sequence Test</h2>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        This will automatically open two modals in sequence. Both should show error toasts when confirmed.
                                    </p>
                                    <button
                                        onClick={testModalSequence}
                                        className="bg-purple-500 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Start Automated Test
                                    </button>
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Manual Modal Tests</h2>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Click these buttons to manually test modal behavior. Each should show an error toast when confirmed.
                                    </p>
                                    <div className="flex gap-2 flex-wrap">
                                        <button
                                            onClick={() => testManualSequence(1)}
                                            className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                        >
                                            Delete Item 1
                                        </button>
                                        <button
                                            onClick={() => testManualSequence(2)}
                                            className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                        >
                                            Delete Item 2
                                        </button>
                                        <button
                                            onClick={() => testManualSequence(3)}
                                            className="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"
                                        >
                                            Delete Item 3
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <h2 className="text-lg font-semibold mb-2">Toast System Verification</h2>
                                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                        Test that the toast system itself is working correctly.
                                    </p>
                                    <button
                                        onClick={testSuccessToast}
                                        className="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded"
                                    >
                                        Test Success Toast
                                    </button>
                                </div>

                                <div className="mt-8 p-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                    <h3 className="font-semibold mb-2">Expected Behavior:</h3>
                                    <ul className="text-sm space-y-1">
                                        <li>✅ Each modal confirmation should show an error toast</li>
                                        <li>✅ Toasts should appear regardless of modal sequence</li>
                                        <li>✅ Console logs should show all confirmations</li>
                                        <li>❌ If second+ modals don't show toasts, the bug is reproduced</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <ConfirmationDialog />
        </AppLayout>
    );
}
