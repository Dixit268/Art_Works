import React, { useEffect } from 'react';
import Button from './Button';

const Modal = ({
  isOpen,
  onClose,
  title = 'Confirmation',
  children,
  confirmText = 'Confirm',
  cancelText = 'Cancel',
  onConfirm = null,
  confirmVariant = 'primary',
  confirmLoading = false,
}) => {
  useEffect(() => {
    if (isOpen) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = 'unset';
    }
    return () => {
      document.body.style.overflow = 'unset';
    };
  }, [isOpen]);

  if (!isOpen) return null;

  return (
    <div className="custom-modal-backdrop" onClick={onClose}>
      <div className="custom-modal-content" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="d-flex align-items-center justify-content-between p-4 border-bottom">
          <h5 className="mb-0 fw-bold brand-font text-dark">{title}</h5>
          <button 
            type="button" 
            className="btn-close shadow-none" 
            aria-label="Close"
            onClick={onClose}
          ></button>
        </div>

        {/* Body */}
        <div className="p-4 text-secondary">
          {children}
        </div>

        {/* Footer */}
        <div className="d-flex align-items-center justify-content-end gap-2 p-3 bg-light border-top">
          <Button variant="light" size="sm" onClick={onClose} disabled={confirmLoading}>
            {cancelText}
          </Button>
          {onConfirm && (
            <Button 
              variant={confirmVariant} 
              size="sm" 
              onClick={onConfirm} 
              loading={confirmLoading}
            >
              {confirmText}
            </Button>
          )}
        </div>
      </div>
    </div>
  );
};

export default Modal;
