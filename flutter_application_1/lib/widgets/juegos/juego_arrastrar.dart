import 'package:flutter/material.dart';

class JuegoArrastrar extends StatefulWidget {
  final Map<String, dynamic> datos;
  // Callback para enviar la respuesta al padre
  final Function(String respuesta) onRespuestaChanged; 

  const JuegoArrastrar({
    super.key, 
    required this.datos, 
    required this.onRespuestaChanged
  });

  @override
  State<JuegoArrastrar> createState() => _JuegoArrastrarState();
}

class _JuegoArrastrarState extends State<JuegoArrastrar> {
  String? palabraColocada;

  @override
  Widget build(BuildContext context) {
    List<dynamic> opciones = widget.datos['opciones'] ?? [];
    String p1 = widget.datos['frase_parte_1'] ?? '';
    String p2 = widget.datos['frase_parte_2'] ?? '';

    return Column(
      children: [
        const Text("Completa la frase:", style: TextStyle(fontSize: 16, color: Colors.grey)),
        const SizedBox(height: 20),
        
        Wrap(
          alignment: WrapAlignment.center,
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 8.0,
          children: [
            Text(p1, style: const TextStyle(fontSize: 18)),
            DragTarget<String>(
              builder: (context, candidates, rejected) {
                return Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: palabraColocada != null ? Colors.orange.shade100 : Colors.grey.shade200,
                    border: Border.all(color: Colors.orange),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    palabraColocada ?? " ... ",
                    style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                  ),
                );
              },
              onAccept: (data) {
                setState(() => palabraColocada = data);
                // Enviamos la frase completa como respuesta
                widget.onRespuestaChanged("$p1 $data $p2");
              },
            ),
            Text(p2, style: const TextStyle(fontSize: 18)),
          ],
        ),
        
        const SizedBox(height: 40),
        
        Wrap(
          spacing: 15,
          runSpacing: 10,
          children: opciones.map((opcion) {
            if (opcion == palabraColocada) return const SizedBox.shrink();
            return Draggable<String>(
              data: opcion,
              feedback: Material(child: _chip(opcion, true)),
              childWhenDragging: Opacity(opacity: 0.5, child: _chip(opcion, false)),
              child: _chip(opcion, false),
            );
          }).toList(),
        )
      ],
    );
  }

  Widget _chip(String text, bool feedback) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: Colors.orange),
        boxShadow: feedback ? [const BoxShadow(blurRadius: 10, color: Colors.black26)] : [],
      ),
      child: Text(text, style: const TextStyle(fontSize: 16, inherit: false, color: Colors.black)),
    );
  }
}